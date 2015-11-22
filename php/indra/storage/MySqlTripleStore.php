<?php

namespace indra\storage;

use indra\exception\ObjectCreationError;
use indra\exception\ObjectNotFoundException;
use indra\object\Attribute;
use indra\object\Object;
use indra\object\Type;
use indra\service\Context;

/**
 * @author Patrick van Bergen
 */
class MySqlTripleStore implements TripleStore
{
    /** @const 20 characters */
    const ATTRIBUTE_TYPE_ID = 'type----------------';

    private function getTypeInformation()
    {
        return [
            'int' => [
                'type' => 'int',
                'encoding' => '',
                'key' => 'value',
            ],
            'date' => [
                'type' => 'date',
                'encoding' => '',
                'key' => 'value',
            ],
            'time' => [
                'type' => 'time',
                'encoding' => '',
                'key' => 'value',
            ],
            'datetime' => [
                'type' => 'datetime',
                'encoding' => '',
                'key' => 'value',
            ],
            'double' => [
                'type' => 'double',
                'encoding' => '',
                'key' => 'value',
            ],
            'varchar' => [
                'type' => 'varchar(255)',
                'encoding' => 'DEFAULT CHARSET = utf8mb4 COLLATE utf8mb4_unicode_ci',
                'key' => 'value(32)',
            ],
            'longtext' => [
                'type' => 'longtext',
                'encoding' => 'DEFAULT CHARSET = utf8mb4 COLLATE utf8mb4_unicode_ci',
                'key' => 'value(32)',
            ],
        ];
    }

    /**
     * @return string[]
     */
    public function getAllDataTypes()
    {
        return array_keys($this->getTypeInformation());
    }

    public function createBasicTables()
    {
        $db = Context::getDB();

        foreach ($this->getTypeInformation() as $name => $info) {
            foreach (['active', 'inactive'] as $state) {

                $db->execute("
                    CREATE TABLE IF NOT EXISTS indra_{$state}_{$name} (
                        `triple_id` char(20) not null,
                        `object_id` char(20) not null,
                        `attribute_id` char(20) not null,
                        `value` {$info['type']} not null,
                        primary key object (`object_id`, `attribute_id`, {$info['key']}),
                        unique key attribute (`attribute_id`, {$info['key']}),
                        unique key triple (`triple_id`)
                    ) engine InnoDB {$info['encoding']}
                ");
            }
        }

        $db->execute("
            CREATE TABLE IF NOT EXISTS indra_revision (
					`revision_id`				char(20) not null,
					`revision_datetime` 		datetime not null,
					`revision_description` 		varchar(255),
					`username`				    varchar(255),
					primary key (`revision_id`),
					key (`revision_datetime`)
            ) engine InnoDB
        ");

        $db->execute("
            CREATE TABLE IF NOT EXISTS indra_revision_action (
					`revision_id`				char(20) not null,
					`triple_id`					char(20) not null,
					`action`					char not null,
					PRIMARY KEY  (`revision_id`, `triple_id`)
            ) engine InnoDB
        ");
    }

    public function save(Object $object)
    {
        $db = Context::getDB();

        $type = $object->getType();
        $attributeValues = $object->getAttributeValues();
        $objectId = $object->getId();

        // type
        $this->writeTriple($objectId, self::ATTRIBUTE_TYPE_ID, $type->getId(), Attribute::TYPE_VARCHAR);

        // existing attribute values
        list($existingValues, $typeFound) = $this->getAttributeValues($object);

//        // changed values
//        $changedValues = [];
//        foreach ($existingValues as $existingId => $existingValue) {
//            if ($existingValue != $attributeValues[$existingName]) {
//                $changedValues[$existingName] = $existingValue;
//                $this->deactivateTriple($objectId, $attribute->getId(), $existingValue, $attribute->getDataType());
//            }
//        }

        // attributes
        foreach ($type->getAttributes() as $attribute) {
            if (isset($attributeValues[$attribute->getName()])) {
                $this->writeTriple($objectId, $attribute->getId(), $attributeValues[$attribute->getName()], $attribute->getDataType());
            }
        }
    }

    public function load(Object $object, $objectId)
    {
        $object->setId($objectId);

        list($attributeValues, $typeFound) = $this->getAttributeValues($object);

        if (!$typeFound) {
            throw new ObjectCreationError('No object of this type and id found.');
        }

        if (empty($attributeValues)) {
            throw new ObjectNotFoundException();
        }

        $object->setAttributeValues($attributeValues);
    }

    private function getAttributeValues(Object $object)
    {
        $db = Context::getDB();

        $type = $object->getType();
        $objectId = $object->getId();
        $attributeValues = [];
        $typeFound = false;

        foreach ($this->getDataTypesOfType($type) as $dataType) {

            $results = $db->queryMultipleRows("
              SELECT `attribute_id`, `value` FROM indra_active_" . $dataType . "
              WHERE
                `object_id` = '" . $objectId . "'
            ");

            foreach ($results as $result) {

                if ($result['attribute_id'] == self::ATTRIBUTE_TYPE_ID) {

                    // type is not stored as an attribute, but only checked for correctness

                    if ($result['value'] == $type->getId()) {
                        $typeFound = true;
                    }

                } else {

                    $attribute = $type->getAttributeById($result['attribute_id']);

                    if ($attribute !== false) {
#todo: this is dangerous; it is needed only when an object has multiple parents; check this better?
                        $attributeName = $attribute->getName();
                        $attributeValues[$attributeName] = $result['value'];
                    }
                }
            }
        }

        return [$attributeValues, $typeFound];
    }

    /**
     * Returns all datatypes that are actually used by $type.
     * (we want to minimize the number of queries)
     *
     * @param Type $type
     * @return string[]
     */
    private function getDataTypesOfType(Type $type)
    {
        // The varchar table contains the type
        $dataTypes = [Attribute::TYPE_VARCHAR => Attribute::TYPE_VARCHAR];

        foreach ($type->getAttributes() as $attribute) {
            $dataTypes[$attribute->getDataType()] = $attribute->getDataType();
        }

        return $dataTypes;
    }

    public function remove(Object $object)
    {
        $db = Context::getDB();

        $objectId = $object->getId();

        foreach ($this->getAllDataTypes() as $dataType) {

            $results = $db->queryMultipleRows("
                SELECT `triple_id`, `object_id`, `attribute_id`, `value` FROM indra_active_" . $dataType . "
                WHERE
                    `object_id` = '" . $objectId . "'
            ");

            foreach ($results as $result) {

                $tripleId = $result['triple_id'];
                $objectId = $result['object_id'];
                $attributeId = $result['attribute_id'];
                $attributeValue = $result['value'];

                $db->execute("
                    INSERT INTO indra_inactive_" . $dataType . "
                    SET
                        `triple_id` = '" . $tripleId . "',
                        `object_id` = '" . $objectId . "',
                        `attribute_id` = '" . $attributeId . "',
                        `value` = '" . $db->esc($attributeValue) . "'
                ");

                $db->execute("
                    DELETE FROM indra_active_" . $dataType . "
                    WHERE
                        `triple_id` = '" . $tripleId . "'
                ");

            }
        }
    }

    /**
     * @param $objectId
     * @param $attributeId
     * @param $attributeValue
     * @param $dataType
     * @throws \indra\exception\DataBaseException
     */
    public function writeTriple($objectId, $attributeId, $attributeValue, $dataType)
    {
        $db = Context::getDB();

        $tripleId = Context::getIdGenerator()->generateId();

        $exists = $db->querySingleCell("
                    SELECT COUNT(*)
                    FROM indra_active_" . $dataType . "
                    WHERE
                        `object_id` = '" . $objectId . "' AND
                        `attribute_id` = '" . $attributeId . "' AND
                        `value` = '" . $db->esc($attributeValue) . "'
                ");

        if (!$exists) {

            $db->execute("
                        INSERT INTO indra_active_" . $dataType . "
                        SET
                            `triple_id` = '" . $tripleId . "',
                            `object_id` = '" . $objectId . "',
                            `attribute_id` = '" . $attributeId . "',
                            `value` = '" . $db->esc($attributeValue) . "'
                    ");
        }
    }
}
