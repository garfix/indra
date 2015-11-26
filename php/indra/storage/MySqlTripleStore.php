<?php

namespace indra\storage;

use indra\exception\DataBaseException;
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
    /** @const 22 characters */
    const ATTRIBUTE_TYPE_ID = 'type------------------';

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
    private function getAllDataTypes()
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
                        `triple_id` binary(22) not null,
                        `object_id` binary(22) not null,
                        `attribute_id` binary(22) not null,
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
					`revision_id`				binary(22) not null,
					`revision_datetime` 		datetime not null,
					`revision_description` 		varchar(255),
					`username`				    varchar(255),
					primary key (`revision_id`),
					key (`revision_datetime`)
            ) engine InnoDB
        ");

        $db->execute("
            CREATE TABLE IF NOT EXISTS indra_revision_action (
					`revision_id`				binary(22) not null,
					`triple_id`					binary(22) not null,
					`action`					char not null,
					PRIMARY KEY  (`revision_id`, `triple_id`)
            ) engine InnoDB
        ");
//
//        $db->execute("
//            CREATE TABLE IF NOT EXISTS indra_revision_object (
//					`revision_id`				binary(22) not null,
//					`object_id`					binary(22) not null
//					PRIMARY KEY  (`revision_id`, `object_id`)
//            ) engine InnoDB
//        ");
    }

    /**
     * @param Revision $revision
     * @throws DataBaseException
     */
    public function storeRevision(Revision $revision)
    {
        $db = Context::getDB();
        $dateTime = Context::getDateTimeGenerator()->getDateTime();

        $db->execute("
            INSERT INTO `indra_revision`
              SET
                  `revision_id` = '" . $revision->getId() . "',
                  `revision_description` = '" . $db->esc($revision->getDescription()) . "',
                  `revision_datetime` = '" . $dateTime->format('Y-m-d H:i:s') . "'
        ");
    }

    public function save(Object $object, $revisionId = null)
    {
        $type = $object->getType();
        $attributeValues = $object->getAttributeValues();
        $objectId = $object->getId();

        // type
        $tripleId = $this->writeTriple($objectId, self::ATTRIBUTE_TYPE_ID, $type->getId(), Attribute::TYPE_VARCHAR, true);
        $this->writeRevisionAction($revisionId, RevisionAction::ACTION_ACTIVATE, $tripleId);

        // attributes
        foreach ($type->getAttributes() as $attribute) {

            $attributeId = $attribute->getId();

            if (isset($attributeValues[$attributeId])) {

                // check if the value has changed
                $tripleData = $this->getTripleData($objectId, $attributeId, $attribute->getDataType());

                // if so, the old value must be deactivated
                if ($tripleData['value'] !== $attributeValues[$attributeId]) {
                    $this->deactivateTriple($tripleData['triple_id'], $objectId, $attributeId, $tripleData['value'], $attribute->getDataType());

                    if ($revisionId !== null) {
                        $this->writeRevisionAction($revisionId, RevisionAction::ACTION_DEACTIVATE, $tripleData['triple_id']);
                    }
                }

                $tripleId = $this->writeTriple($objectId, $attributeId, $attributeValues[$attributeId], $attribute->getDataType(), true);
                $this->writeRevisionAction($revisionId, RevisionAction::ACTION_ACTIVATE, $tripleId);
            }
        }

//        if ($revisionId) {
//            $this->writeRevisionObject($revisionId, $objectId);
//        }
    }

    public function load(Object $object)
    {
        list($attributeValues, $typeFound) = $this->getAttributeValues($object);

        if (!$typeFound) {
            throw new ObjectCreationError('No object of this type and id found.');
        }

        if (empty($attributeValues)) {
            throw new ObjectNotFoundException();
        }

        $object->setAttributeValues($attributeValues);
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

                $this->deactivateTriple($tripleId, $objectId, $attributeId, $attributeValue, $dataType);
            }
        }
    }

    public function revertRevision(Revision $revision, Revision $undoRevision)
    {
        $this->storeRevision($undoRevision);

        $activationTripleIds = [];
        $deactivationTripleIds = [];
        foreach ($this->getRevisionActions($revision) as $revisionAction) {
            if ($revisionAction->getAction() == RevisionAction::ACTION_ACTIVATE) {
                $deactivationTripleIds[] = $revisionAction->getTripleId();
                $this->writeRevisionAction($undoRevision->getId(), RevisionAction::ACTION_DEACTIVATE, $revisionAction->getTripleId());
            } else {
                $activationTripleIds[] = $revisionAction->getTripleId();
                $this->writeRevisionAction($undoRevision->getId(), RevisionAction::ACTION_ACTIVATE, $revisionAction->getTripleId());
            }
        }

        $this->deactivateTriples($deactivationTripleIds);
        $this->activateTriples($activationTripleIds);
    }

//    public function getRevisionObjectIds(Revision $revision)
//    {
//        $db = Context::getDB();
//
//        $q = "
//            SELECT `object_id`
//            FROM `indra_revision_object`
//            WHERE `revision_id` = '" . $db->esc($revision->getId())  . "'
//        ";
//
//        return $db->querySingleColumn($q);
//    }

//    private function writeRevisionObject($revisionId, $objectId)
//    {
//        $db = Context::getDB();
//
//        $db->execute("
//            INSERT INTO `indra_revision_object`
//              SET
//                  `revision_id` = '" . $revisionId . "',
//                  `object_id` = '" . $objectId . "'
//        ");
//    }

    private function writeRevisionAction($revisionId, $action, $tripleId)
    {
        $db = Context::getDB();

        $db->execute("
            INSERT INTO `indra_revision_action`
              SET
                  `revision_id` = '" . $revisionId . "',
                  `triple_id` = '" . $tripleId . "',
                  `action` = '" . $action . "'
        ");
    }

    private function getTripleData($objectId, $attributeId, $dataType)
    {
        return Context::getDB()->querySingleRow("
            SELECT `triple_id`, `value`
            FROM indra_active_" . $dataType . "
            WHERE
                `object_id` = '" . $objectId . "' AND
                `attribute_id` = '" . $attributeId . "'
        ");
    }


    /**
     * @param Revision $revision
     * @return RevisionAction[]
     * @throws DataBaseException
     */
    private function getRevisionActions(Revision $revision)
    {
        $db = Context::getDB();

        $q = "
            SELECT `triple_id`, `action`
            FROM `indra_revision_action`
            WHERE `revision_id` = '" . $db->esc($revision->getId())  . "'
        ";

        $actions = [];

        foreach ($db->queryMultipleRows($q) as $row) {
            $actions[] = new RevisionAction($row['triple_id'], $row['action']);
        }

        return $actions;
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

                    $attributeValues[$result['attribute_id']] = $result['value'];

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

    /**
     * @param $objectId
     * @param $attributeId
     * @param $attributeValue
     * @param $dataType
     * @param bool $active
     * @return string Triple id
     * @throws DataBaseException
     */
    private function writeTriple($objectId, $attributeId, $attributeValue, $dataType, $active)
    {
        $db = Context::getDB();

        $tripleId = Context::getIdGenerator()->generateId();
        $activeness = $active ? 'active' : 'inactive';

        $exists = $db->querySingleCell("
                    SELECT COUNT(*)
                    FROM indra_" . $activeness . "_" . $dataType . "
                    WHERE
                        `object_id` = '" . $objectId . "' AND
                        `attribute_id` = '" . $attributeId . "' AND
                        `value` = '" . $db->esc($attributeValue) . "'
                ");

        if (!$exists) {

            $db->execute("
                        INSERT INTO indra_" . $activeness . "_" . $dataType . "
                        SET
                            `triple_id` = '" . $tripleId . "',
                            `object_id` = '" . $objectId . "',
                            `attribute_id` = '" . $attributeId . "',
                            `value` = '" . $db->esc($attributeValue) . "'
                    ");
        }

        return $tripleId;
    }

    private function deactivateTriple($tripleId, $objectId, $attributeId, $attributeValue, $dataType)
    {
        $db = Context::getDB();

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

    private function deactivateTriples($tripleIds)
    {
        $db = Context::getDB();

        if (empty($tripleIds)) {
            return;
        }

        foreach ($this->getAllDataTypes() as $dataType) {

            $db->execute("
                INSERT INTO `indra_inactive_" . $dataType . "` (`triple_id`, `object_id`, `attribute_id`, `value`)
                SELECT `triple_id`, `object_id`, `attribute_id`, `value`
                FROM `indra_active_" . $dataType . "`
                WHERE
                    `triple_id` IN ('" . implode("', '", $tripleIds) . "')
            ");

            $db->execute("
                DELETE FROM indra_active_" . $dataType . "
                WHERE
                    `triple_id` IN ('" . implode("', '", $tripleIds) . "')
            ");
        }
    }

    private function activateTriples($tripleIds)
    {
        $db = Context::getDB();

        if (empty($tripleIds)) {
            return;
        }

        foreach ($this->getAllDataTypes() as $dataType) {

            $db->execute("
                INSERT INTO `indra_active_" . $dataType . "` (`triple_id`, `object_id`, `attribute_id`, `value`)
                SELECT `triple_id`, `object_id`, `attribute_id`, `value`
                FROM `indra_inactive_" . $dataType . "`
                WHERE
                    `triple_id` IN ('" . implode("', '", $tripleIds) . "')
            ");

            $db->execute("
                DELETE FROM indra_inactive_" . $dataType . "
                WHERE
                    `triple_id` IN ('" . implode("', '", $tripleIds) . "')
            ");
        }
    }
}
