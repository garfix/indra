<?php

namespace indra\storage;

use indra\exception\DataBaseException;
use indra\exception\ObjectCreationError;
use indra\exception\ObjectNotFoundException;
use indra\object\Attribute;
use indra\object\DomainObject;
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

                $db->execute("
                    CREATE TABLE IF NOT EXISTS indra_branch_{$state}_{$name} (
                        `branch_id` binary(22) not null,
                        `triple_id` binary(22) not null,
                        `object_id` binary(22) not null,
                        `attribute_id` binary(22) not null,
                        `value` {$info['type']} not null,
                        primary key object (`branch_id`, `object_id`, `attribute_id`, {$info['key']}),
                        unique key attribute (`branch_id`, `attribute_id`, {$info['key']}),
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
					`source_revision_id` 		binary(22),
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

        $db->execute("
            CREATE TABLE IF NOT EXISTS indra_branch (
					`branch_id`				    binary(22) not null,
					`revision_id` 		        binary(22) not null,
					primary key (`branch_id`)
            ) engine InnoDB
        ");
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
                  `source_revision_id` = '" . $revision->getSourceRevision()->getId() . "',
                  `revision_description` = '" . $db->esc($revision->getDescription()) . "',
                  `revision_datetime` = '" . $dateTime->format('Y-m-d H:i:s') . "'
        ");
    }

    public function saveBranch(Branch $branch)
    {
        $db = Context::getDB();
        $revisionId = $branch->getActiveRevision()->getId();

        $db->execute("
            INSERT INTO `indra_branch`
                SET
                      `branch_id` = '" . $branch->getId() . "',
                      `revision_id` = '" . $revisionId . "'
                ON DUPLICATE KEY UPDATE
                       `revision_id` = '" . $revisionId . "'
        ");
    }

    public function loadBranch($branchId)
    {
        $db = Context::getDB();

        $branch = new Branch($branchId);

        $revisionId = $db->querySingleCell("
            SELECT `revision_id`
            FROM `indra_branch`
            WHERE `branch_id` = '" . $branchId . "'
        ");

        if ($revisionId) {
            $revision = new Revision($revisionId);
        } else {
            $revision = new BaseRevision();
        }
        $branch->setActiveRevision($revision);

        return $branch;
    }

    public function save(DomainObject $object, Revision $revision, Branch $branch)
    {
        $revisionId = $revision->getId();
        $type = $object->getType();
        $attributeValues = $object->getAttributeValues();
        $objectId = $object->getId();

        // type
        if ($tripleId = $this->writeTriple($objectId, self::ATTRIBUTE_TYPE_ID, $type->getId(), Attribute::TYPE_VARCHAR, true, $branch)) {
#todo: only if the type changes
            if (1) {
                $this->writeRevisionAction($revisionId, RevisionAction::ACTION_ACTIVATE, $tripleId);
            }
        }

        // attributes
        foreach ($type->getAttributes() as $attribute) {

            $attributeId = $attribute->getId();

            if (isset($attributeValues[$attributeId])) {

                // check if the value has changed
                $tripleData = $this->getTripleData($objectId, $attributeId, $attribute->getDataType(), $branch);

                // if so, the old value must be deactivated
                if ($tripleData) {
                    if ($tripleData['value'] !== $attributeValues[$attributeId]) {
                        $this->deactivateTriple($tripleData['triple_id'], $objectId, $attributeId, $tripleData['value'], $attribute->getDataType(), $branch);
                        $this->writeRevisionAction($revisionId, RevisionAction::ACTION_DEACTIVATE, $tripleData['triple_id']);
                    }
                }

                if ($tripleId = $this->writeTriple($objectId, $attributeId, $attributeValues[$attributeId], $attribute->getDataType(), true, $branch)) {

                    if (!isset($object->originalAttributes[$attributeId]) || ($object->originalAttributes[$attributeId] != $attributeValues[$attributeId])) {
                        $this->writeRevisionAction($revisionId, RevisionAction::ACTION_ACTIVATE, $tripleId);
                    }
                }
            }
        }
    }

    public function loadAttributes(Type $type, $objectId, Branch $branch)
    {
        list($attributeValues, $typeFound) = $this->getAttributeValues($type, $objectId, $branch);

        if (!$typeFound) {
            throw new ObjectCreationError('No object of this type and id found.');
        }

        if (empty($attributeValues)) {
            throw new ObjectNotFoundException();
        }

        return $attributeValues;
    }

    public function remove(DomainObject $object, Branch $branch)
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

                $this->deactivateTriple($tripleId, $objectId, $attributeId, $attributeValue, $dataType, $branch);
            }
        }
    }

    public function getSourceRevisionId($revisionId)
    {
        $db = Context::getDB();

        return $db->querySingleCell("
            SELECT `source_revision_id`
            FROM `indra_revision`
            WHERE `revision_id` = '" . $revisionId . "'
        ");

    }

    public function revertRevision(Branch $branch, Revision $revision, Revision $undoRevision)
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

        $this->moveTriples($branch, $branch, $deactivationTripleIds, true, false);
        $this->moveTriples($branch, $branch, $activationTripleIds, false, true);
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

        if ($revisionId === null) {
            return;
        }

        if ($revisionId == BaseRevision::ID) {
            return;
        }

        $db->execute("
            INSERT INTO `indra_revision_action`
              SET
                  `revision_id` = '" . $revisionId . "',
                  `triple_id` = '" . $tripleId . "',
                  `action` = '" . $action . "'
        ");
    }

    private function getTripleData($objectId, $attributeId, $dataType, Branch $branch)
    {
        $branchToken = $branch->isMaster() ? '' : 'branch_';
        $branchClause = $branch->isMaster() ? "" : "`branch_id` = '" . $branch->getId() . "' AND\n";

        return Context::getDB()->querySingleRow("
            SELECT `triple_id`, `value`
            FROM indra_{$branchToken}active_" . $dataType . "
            WHERE
                {$branchClause}
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

    private function getAttributeValues(Type $type, $objectId, Branch $branch)
    {
        $db = Context::getDB();

        $attributeValues = [];
        $branchToken = ($branch->isMaster()) ? '' : 'branch_';
        $branchClause = ($branch->isMaster()) ? "" : "`branch_id` = '" . $branch->getId() . "' AND\n";
        $typeFound = false;

        foreach ($this->getDataTypesOfType($type) as $dataType) {

            $results = $db->queryMultipleRows("
              SELECT `attribute_id`, `value` FROM indra_{$branchToken}active_" . $dataType . "
              WHERE
                {$branchClause}
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
     * @param Branch $branch
     * @return null|string Triple id If (object/attribute/value) already existed, return null; otherwise: the new triple id
     * @throws DataBaseException
     */
    private function writeTriple($objectId, $attributeId, $attributeValue, $dataType, $active, Branch $branch)
    {
        $db = Context::getDB();

        $activeness = $active ? 'active' : 'inactive';

        $branchToken = $branch->isMaster() ? '' : 'branch_';
        $branchClause = $branch->isMaster() ? "" : "`branch_id` = '" . $branch->getId() . "' AND\n";

        $exists = $db->querySingleCell("
                    SELECT COUNT(*)
                    FROM indra_" . $branchToken . $activeness . "_" . $dataType . "
                    WHERE
                        {$branchClause}
                        `object_id` = '" . $objectId . "' AND
                        `attribute_id` = '" . $attributeId . "' AND
                        `value` = '" . $db->esc($attributeValue) . "'
                ");

        if (!$exists) {

            $branchClause = $branch->isMaster() ? "" : "`branch_id` = '" . $branch->getId() . "',\n";

            $tripleId = Context::getIdGenerator()->generateId();

            $db->execute("
                INSERT IGNORE INTO indra_" . $branchToken . $activeness . "_" . $dataType . "
                SET
                    {$branchClause}
                    `triple_id` = '" . $tripleId . "',
                    `object_id` = '" . $objectId . "',
                    `attribute_id` = '" . $attributeId . "',
                    `value` = '" . $db->esc($attributeValue) . "'
                    ");

            return $tripleId;
        }

        return null;
    }

    private function deactivateTriple($tripleId, $objectId, $attributeId, $attributeValue, $dataType, Branch $branch)
    {
        $db = Context::getDB();

        $branchToken = $branch->isMaster() ? '' : 'branch_';
        $branchClause = $branch ->isMaster() ? "" : "`branch_id` = '" . $branch->getId() . "' AND\n";

        $db->execute("
                    INSERT INTO indra_{$branchToken}inactive_" . $dataType . "
                    SET
                        {$branchClause}
                        `triple_id` = '" . $tripleId . "',
                        `object_id` = '" . $objectId . "',
                        `attribute_id` = '" . $attributeId . "',
                        `value` = '" . $db->esc($attributeValue) . "'
                ");

        $db->execute("
                    DELETE FROM indra_{$branchToken}active_" . $dataType . "
                    WHERE
                        `triple_id` = '" . $tripleId . "'
                ");

    }

//    private function deactivateTriples(Branch $fromBranch, Branch $toBranch, $tripleIds)
//    {
//        $db = Context::getDB();
//
//        if (empty($tripleIds)) {
//            return;
//        }
//
//
//        $fromBranchToken = ($fromBranch instanceof MasterBranch) ? '' : 'branch_';
//        $fromBranchClause = ($fromBranch instanceof MasterBranch) ? "" : "`branch_id` = '" . $fromBranch->getId() . "' AND\n";
//
//        $toBranchToken = ($toBranch instanceof MasterBranch) ? '' : 'branch_';
//        $toBranchClause = ($toBranch instanceof MasterBranch) ? "" : "`branch_id` = '" . $toBranch->getId() . "' AND\n";
//
//        foreach ($this->getAllDataTypes() as $dataType) {
//
//            $db->execute("
//                INSERT INTO `indra_{$toBranchToken}inactive_" . $dataType . "` (`triple_id`, `object_id`, `attribute_id`, `value`)
//                SELECT `triple_id`, `object_id`, `attribute_id`, `value`
//                FROM `indra_{$fromBranchToken}active_" . $dataType . "`
//                WHERE
//                    {$fromBranchClause}
//                    `triple_id` IN ('" . implode("', '", $tripleIds) . "')
//            ");
//
//            $db->execute("
//                DELETE FROM indra_{$fromBranchToken}active_" . $dataType . "
//                WHERE
//                    {$fromBranchClause}
//                    `triple_id` IN ('" . implode("', '", $tripleIds) . "')
//            ");
//        }
//    }

    private function moveTriples(Branch $fromBranch, Branch $toBranch, $tripleIds, $fromActive, $toActive)
    {
        $db = Context::getDB();

        if (empty($tripleIds)) {
            return;
        }

        $fromBranchToken = $fromBranch->isMaster() ? '' : 'branch_';
        $fromBranchClause = $fromBranch->isMaster() ? "" : "`branch_id` = '" . $fromBranch->getId() . "' AND\n";
        $fromActiveness = $fromActive ? 'active_' : 'inactive_';

        $toBranchToken = $toBranch->isMaster() ? '' : 'branch_';
        $toActiveness = $toActive ? 'active_' : 'inactive_';

        foreach ($this->getAllDataTypes() as $dataType) {

            $db->execute("
                INSERT ignore INTO `indra_{$toBranchToken}{$toActiveness}" . $dataType . "` (`triple_id`, `object_id`, `attribute_id`, `value`)
                SELECT `triple_id`, `object_id`, `attribute_id`, `value`
                FROM `indra_{$fromBranchToken}{$fromActiveness}" . $dataType . "`
                WHERE
                    {$fromBranchClause}
                    `triple_id` IN ('" . implode("', '", $tripleIds) . "')
            ");

            $swapActiveness = $toActiveness == 'active_' ? 'inactive_' : 'active_';

            $db->execute("
                DELETE FROM indra_{$fromBranchToken}{$swapActiveness}" . $dataType . "
                WHERE
                    {$fromBranchClause}
                    `triple_id` IN ('" . implode("', '", $tripleIds) . "')
            ");
        }
    }

    public function mergeRevisions(Branch $fromBranch, Branch $toBranch, Revision $mergeRevision, $revisionIds)
    {
// make sure that there are no activations and deactivations of the same revision
        $this->storeRevision($mergeRevision);

        $activationTripleIds = [];
        $deactivationTripleIds = [];
        foreach ($revisionIds as $revisionId) {
            $revision = new Revision($revisionId);
            foreach ($this->getRevisionActions($revision) as $revisionAction) {
                if ($revisionAction->getAction() == RevisionAction::ACTION_ACTIVATE) {
                    $activationTripleIds[] = $revisionAction->getTripleId();
                    $this->writeRevisionAction($mergeRevision->getId(), RevisionAction::ACTION_ACTIVATE, $revisionAction->getTripleId());
                } else {
                    $deactivationTripleIds[] = $revisionAction->getTripleId();
                    $this->writeRevisionAction($mergeRevision->getId(), RevisionAction::ACTION_DEACTIVATE, $revisionAction->getTripleId());
                }
            }
        }

        $this->moveTriples($fromBranch, $toBranch, $deactivationTripleIds, false, false);
        $this->moveTriples($fromBranch, $toBranch, $activationTripleIds, true, true);
    }
}
