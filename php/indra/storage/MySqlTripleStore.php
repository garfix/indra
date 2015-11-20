<?php

namespace indra\storage;

use Exception;
use indra\exception\ObjectNotFoundException;
use indra\object\Object;
use indra\service\Context;
use indra\service\IdGenerator;

/**
 * @author Patrick van Bergen
 */
class MySqlTripleStore implements TripleStore
{
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
        $mysqli = Context::getMySqli();

        foreach ($this->getTypeInformation() as $name => $info) {
            foreach (['active', 'passive'] as $state) {

                $mysqli->query("
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

        $mysqli->query("
            CREATE TABLE IF NOT EXISTS indra_revision (
					`revision_id`				char(20) not null,
					`revision_datetime` 		datetime not null,
					`revision_description` 		varchar(255),
					`username`				    varchar(255),
					primary key (`revision_id`),
					key (`revision_datetime`)
            ) engine InnoDB
        ");

        $mysqli->query("
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
        $mysqli = Context::getMySqli();

        $type = $object->getType();
        $attributeValues = $object->getAttributeValues();
        $objectId = $object->getId();

        foreach ($type->getAttributes() as $attribute) {

            if (isset($attributeValues[$attribute->getName()])) {

                $attributeValue = $attributeValues[$attribute->getName()];
                $attributeId = $attribute->getId();
                $dataType = $attribute->getDataType();
                $tripleId = IdGenerator::generateId();

                $q = "
                  INSERT INTO indra_active_" . $dataType . "
                  SET
                    `triple_id` = '" . $tripleId . "',
                    `object_id` = '" . $objectId . "',
                    `attribute_id` = '" . $attributeId . "',
                    `value` = '" . mysqli_real_escape_string($mysqli, $attributeValue) . "'
                ";

#                var_dump($q);

                $mysqli->query($q);
            }
        }
    }

    public function load(Object $object, $objectId)
    {
        $mysqli = Context::getMySqli();

        $object->setId($objectId);

        $type = $object->getType();

        // find the applicable data types of the type
        // (we want to minimize the number of queries)
        $dataTypes = [];
        foreach ($type->getAttributes() as $attribute) {
            $dataTypes[$attribute->getDataType()] = $attribute->getDataType();
        }

        $attributeValues = [];

        foreach ($dataTypes as $dataType) {

            $resultSet = $mysqli->query("
              SELECT `attribute_id`, `value` FROM indra_active_" . $dataType . "
              WHERE
                `object_id` = '" . $objectId . "'
            ");

            if ($resultSet) {
                while ($result = $resultSet->fetch_assoc()) {
                    $attributeName = $type->getAttributeById($result['attribute_id'])->getName();
                    $attributeValues[$attributeName] = $result['value'];
                }
            } else {
                throw new Exception();
            }
        }

        if (empty($attributeValues)) {
            throw new ObjectNotFoundException();
        }

        $object->setAttributeValues($attributeValues);
    }

    public function remove(Object $object)
    {
        $mysqli = Context::getMySqli();

        $objectId = $object->getId();

        foreach ($this->getAllDataTypes() as $dataType) {

            $resultSet = $mysqli->query("
                SELECT `triple_id`, `object_id`, `attribute_id`, `value` FROM indra_active_" . $dataType . "
                WHERE
                    `object_id` = '" . $objectId . "'
            ");

            if ($resultSet) {
                while ($result = $resultSet->fetch_assoc()) {

                    $tripleId = $result['triple_id'];
                    $objectId = $result['object_id'];
                    $attributeId = $result['attribute_id'];
                    $attributeValue = $result['value'];

                    $mysqli->query("
                        INSERT INTO indra_inactive_" . $dataType . "
                        SET
                            `triple_id` = '" . $tripleId . "',
                            `object_id` = '" . $objectId . "',
                            `attribute_id` = '" . $attributeId . "',
                            `value` = '" . mysqli_real_escape_string($mysqli, $attributeValue) . "'
                    ");

                    $mysqli->query("
                        DELETE FROM indra_active_" . $dataType . "
                        WHERE
                            `triple_id` = '" . $tripleId . "'
                    ");

                }
            } else {
                throw new Exception();
            }
        }
    }
}
