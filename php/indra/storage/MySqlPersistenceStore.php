<?php

namespace indra\storage;

use indra\diff\AttributeValuesChanged;
use indra\diff\DiffItem;
use indra\diff\ObjectAdded;
use indra\exception\DataBaseException;
use indra\exception\DiffItemClassNotRecognizedException;
use indra\exception\ObjectNotFoundException;
use indra\object\DomainObject;
use indra\object\Type;
use indra\service\Context;

/**
 * @author Patrick van Bergen
 */
class MySqlPersistenceStore implements PersistenceStore
{
    /** @const 22 characters */
    const ATTRIBUTE_TYPE_ID = 'type------------------';

//    private function getTypeInformation()
//    {
//        return [
//            'int' => [
//                'type' => 'int',
//                'encoding' => '',
//                'key' => 'value',
//            ],
//            'date' => [
//                'type' => 'date',
//                'encoding' => '',
//                'key' => 'value',
//            ],
//            'time' => [
//                'type' => 'time',
//                'encoding' => '',
//                'key' => 'value',
//            ],
//            'datetime' => [
//                'type' => 'datetime',
//                'encoding' => '',
//                'key' => 'value',
//            ],
//            'double' => [
//                'type' => 'double',
//                'encoding' => '',
//                'key' => 'value',
//            ],
//            'varchar' => [
//                'type' => 'varchar(255)',
//                'encoding' => 'DEFAULT CHARSET = utf8mb4 COLLATE utf8mb4_unicode_ci',
//                'key' => 'value(32)',
//            ],
//            'longtext' => [
//                'type' => 'longtext',
//                'encoding' => 'DEFAULT CHARSET = utf8mb4 COLLATE utf8mb4_unicode_ci',
//                'key' => 'value(32)',
//            ],
//        ];
//    }

    public function createBasicTables()
    {
        $db = Context::getDB();

        $db->execute("
            CREATE TABLE IF NOT EXISTS indra_branch (
					`branch_id`				    binary(22) not null,
					`commit_index`              int not null,
					`mother_branch_id`	        binary(22),
					`mother_commit_index`       int,
					primary key (`branch_id`)
            ) engine InnoDB
        ");

        $db->execute("
            CREATE TABLE IF NOT EXISTS indra_commit (
					`branch_id`				    binary(22) not null,
					`commit_index`              int not null,
					`reason`	                varchar(255),
					`username`	                varchar(255),
					`datetime`                  datetime,
					`merge_branch_id`			binary(22) default null,
					`merge_commit_index`        int default null,
					primary key (`branch_id`, `commit_index`)
            ) engine InnoDB
        ");

        $db->execute("
            CREATE TABLE IF NOT EXISTS indra_commit_type (
					`branch_id`				    binary(22) not null,
					`type_id`	                binary(22) not null,
					`commit_index`              int not null,
					`diff`                      longtext,
					primary key (`branch_id`, `type_id`, `commit_index`)
            ) engine InnoDB
        ");

        $db->execute("
            CREATE TABLE IF NOT EXISTS indra_branch_view (
					`branch_id`				    binary(22) not null,
					`type_id`				    binary(22) not null,
					`view_id`	                binary(22) not null,
					primary key (`branch_id`, `type_id`)
            ) engine InnoDB
        ");

        $db->execute("
            CREATE TABLE IF NOT EXISTS indra_snapshot (
					`branch_id`				    binary(22) not null,
					`commit_index`			    int not null,
					`type_id`				    binary(22) not null,
					`view_id`	                binary(22) not null,
					primary key (`branch_id`, `commit_index`)
            ) engine InnoDB
        ");
    }

    /**
     * @param Commit $commit
     * @throws DataBaseException
     */
    public function storeCommit(Commit $commit)
    {
        $db = Context::getDB();

        $db->execute("
            INSERT INTO `indra_commit`
              SET
                  `branch_id` = '" . $commit->getBranchId() . "',
                  `commit_index` = '" . $commit->getCommitIndex() . "',
                  `reason` = '" . $commit->getReason() . "',
                  `username` = '" . $commit->getUserName() . "',
                  `datetime` = '" . $commit->getDateTime() . "',
                  `merge_branch_id` = '" . $commit->getMergeBranchId() . "',
                  `merge_commit_index` = '" . $commit->getMergeCommitIndex() . "'
        ");
    }

    public function storeBranch(Branch $branch)
    {
        $db = Context::getDB();
        $motherBranchId = $branch->getMotherBranchId();
        $motherCommitIndex = $branch->getMotherCommitIndex();

        $db->execute("
            INSERT INTO `indra_branch`
                SET
                      `branch_id` = '" . $branch->getBranchId() . "',
                      `commit_index` = '" . $branch->getCommitIndex() . "',
                      `mother_branch_id` = " . ($motherBranchId ? "'" . $motherBranchId . "'" : 'null') . ",
                      `mother_commit_index` = " . ($motherCommitIndex ? $motherCommitIndex : 'null') . "
                ON DUPLICATE KEY UPDATE
                      `commit_index` = '" . $branch->getCommitIndex() . "'
        ");
    }

    public function loadBranch($branchId)
    {
        $db = Context::getDB();

        $branchData = $db->querySingleRow("
            SELECT `commit_index`, `mother_branch_id`, `mother_commit_index`
            FROM `indra_branch`
            WHERE `branch_id` = '" . $branchId . "'
        ");

        if ($branchData) {

            $branch = new Branch($branchId, $branchData['mother_branch_id'], $branchData['mother_commit_index']);
            $branch->setCommitIndex($branchData['commit_index']);

            return $branch;

        } else {
            return null;
        }
    }

    public function loadAttributes($objectId, TableView $view)
    {
        $db = Context::getDB();

        $attributeValues = $db->querySingleRow("
            SELECT * FROM `" . $view->getTableName() . "`
            WHERE id = '" . $db->esc($objectId) . "'
        ");

        if (empty($attributeValues)) {
            throw new ObjectNotFoundException();
        }

        return $attributeValues;
    }

    public function remove(DomainObject $object, Branch $branch)
    {
        $db = Context::getDB();

        $objectId = $object->getId();

        $branchView = $this->getBranchView($branch->getBranchId(), $object->getType()->getId());

        $db->execute("
            DELETE FROM `" . $branchView->getTableName() . "`
            WHERE id = '" . $db->esc($objectId) . "'
        ");
    }

    public function storeDomainObjectTypeCommit(DomainObjectTypeCommit $dotCommit)
    {
        $db = Context::getDB();

        $serializer = new DiffService();
        $diff = $serializer->serializeDiffItems($dotCommit->getDiffItems());

        $db->execute("
            INSERT INTO `indra_commit_type`
                SET
                      `branch_id` = '" . $dotCommit->getBranchId() . "',
                      `commit_index` = '" . $dotCommit->getCommitIndex() . "',
                      `type_id` = '" . $dotCommit->getTypeId() . "',
                      `diff` = '" . $diff . "'
        ");
    }

    public function getDomainObjectTypeCommits(Commit $commit)
    {
        $db = Context::getDB();

        $rows = $db->queryMultipleRows("
            SELECT type_id, diff
            FROM indra_commit_type
            WHERE branch_id = '" . $commit->getBranchId() . "' AND commit_index = '" . $commit->getCommitIndex() . "'
        ");

        $serializer = new DiffService();

        $dotCommits = [];

        foreach ($rows as $row) {

            $diffItems = $serializer->deserializeDiffItems($row['diff']);
            $dotCommit = new DomainObjectTypeCommit($commit->getBranchId(), $row['type_id'], $commit->getCommitIndex(), $diffItems);

            $dotCommits[] = $dotCommit;
        }

        return $dotCommits;
    }

    /**
     * @param Commit $commit
     * @param Type $type
     * @return DomainObjectTypeCommit[]
     */
    public function getDomainObjectTypeCommitsForType(Commit $commit, Type $type)
    {
        $db = Context::getDB();

        $rows = $db->queryMultipleRows("
            SELECT type_id, diff
            FROM indra_commit_type
            WHERE branch_id = '" . $commit->getBranchId() . "' AND commit_index = '" . $commit->getCommitIndex() . "' AND type_id = '" . $type->getId() . "'
        ");

        $serializer = new DiffService();

        $dotCommits = [];

        foreach ($rows as $row) {

            $diffItems = $serializer->deserializeDiffItems($row['diff']);
            $dotCommit = new DomainObjectTypeCommit($commit->getBranchId(), $type->getId(), $commit->getCommitIndex(), $diffItems);

            $dotCommits[] = $dotCommit;
        }

        return $dotCommits;
    }

    public function getNumberOfBranchesUsingView(BranchView $branchView)
    {
        $db = Context::getDB();

        return $db->querySingleCell("
            SELECT COUNT(*)
            FROM `indra_branch_view`
            WHERE `view_id` = '". $db->esc($branchView->getViewId()) . "'
        ");
    }

    public function getBranchView($branchId, $typeId)
    {
        $db = Context::getDB();

        $viewId = $db->querySingleCell("
            SELECT view_id
            FROM indra_branch_view
            WHERE branch_id = '" . $branchId . "' AND type_id = '" . $typeId . "'");

        if ($viewId) {
            $branchView = new BranchView($branchId, $typeId, $viewId);
        } else {
            $branchView = null;
        }

        return $branchView;
    }

    /**
     * @param Commit $commit
     * @param string $typeId
     * @return Snapshot|null
     * @throws DataBaseException
     */
    public function loadSnapshot(Commit $commit, $typeId)
    {
        $db = Context::getDB();

        $branchId = $commit->getBranchId();
        $commitIndex = $commit->getCommitIndex();

        $viewId = $db->querySingleCell("
            SELECT view_id
            FROM indra_snapshot
            WHERE branch_id = '" . $branchId . "' AND commit_index = '" . $commitIndex . "' AND type_id = '" . $typeId . "'");

        if ($viewId) {
            $snapshot = new Snapshot($branchId, $commit, $typeId, $viewId);
        } else {
            $snapshot = null;
        }

        return $snapshot;
    }

    public function storeBranchView(BranchView $branchView, Type $type)
    {
        $db = Context::getDB();

        $db->execute("
            INSERT INTO indra_branch_view
            SET branch_id = '" . $branchView->getBranchId() . "',
                type_id = '" . $branchView->getTypeId() . "',
                view_id = '" . $branchView->getViewId() . "'
        ");

        $this->createTableForView($branchView, $type);
    }

    public function storeSnapshot(Snapshot $snapshot, BranchView $branchView)
    {
        $db = Context::getDB();

        $db->execute("
            INSERT INTO indra_snapshot
            SET branch_id = '" . $snapshot->getBranchId() . "',
                commit_index = '" . $snapshot->getCommitIndex() . "',
                type_id = '" . $snapshot->getTypeId() . "',
                view_id = '" . $snapshot->getViewId() . "'
        ");

        $this->cloneTableView($snapshot, $branchView);
    }

    public function createTableForView(TableView $tableView, Type $type)
    {
        $db = Context::getDB();

        $columns = [];

        foreach ($type->getAttributes() as $attribute) {
            $columns[$attribute->getId()] = $this->getMySqlDataType($attribute->getDataType());
        }

        $fields = "";
        foreach ($columns as $id => $dataType) {
            $fields .= $id . ' ' . $this->getMySqlDataType($dataType) . ",\n";
        }

        // when testing we don't want real tables;
        // not just because they have to be removed, but especially because a 'create table' statement _implicitly commits the transaction_
        $temporary = Context::inTestMode() ? "TEMPORARY" : "";

        $db->execute("
            CREATE {$temporary} TABLE " . $tableView->getTableName() . " (
                `id` binary(22) NOT NULL,
                {$fields}
                primary key (`id`)
            ) engine InnoDB"
        );
    }

    public function processDiffItem(TableView $tableView, DiffItem $diffItem)
    {
        $db = Context::getDB();

        if ($diffItem instanceof AttributeValuesChanged) {

            $values = $this->createValueClause($diffItem->getAttributeValues());

            $db->execute("
                UPDATE `" . $tableView->getTableName() . "`
                SET {$values}
                WHERE id = '" . $db->esc($diffItem->getObjectId()) . "'
            ");

        } elseif ($diffItem instanceof ObjectAdded) {

            $values = $this->createValueClause($diffItem->getAttributeValues());

            $db->execute("
                INSERT INTO `" . $tableView->getTableName() . "`
                SET id = '" . $db->esc($diffItem->getObjectId()) . "',
                {$values}
            ");

        } else {
            throw new DiffItemClassNotRecognizedException();
        }
    }

    public function cloneBranchView(BranchView $newBranchView, BranchView $oldBranchView)
    {
        $db = Context::getDB();

        $db->execute("
            UPDATE indra_branch_view
            SET view_id = '" . $newBranchView->getViewId() . "'
            WHERE branch_id = '" . $oldBranchView->getBranchId() . "' AND type_id = '" . $oldBranchView->getTypeId() . "'
        ");

        $this->cloneTableView($newBranchView, $oldBranchView);
    }

    /**
     * When a branch is created, it just makes a shallow copy of all of the views of the mother branch.
     * When one of the branches sharing the shallow copy changes a view of a type,
     * it must make a full copy of the view. That's what happens here.
     *
     * @param TableView $newTableView
     * @param TableView $oldTableView
     * @throws DataBaseException
     */
    public function cloneTableView(TableView $newTableView, TableView $oldTableView)
    {
        $db = Context::getDB();

        // when testing we don't want real tables;
        // not just because they have to be removed, but especially because a 'create table' statement _implicitly commits the transaction_
        $temporary = Context::inTestMode() ? "TEMPORARY" : "";

        $db->execute("
            CREATE {$temporary} TABLE " . $newTableView->getTableName() . " AS
            SELECT * FROM " . $oldTableView->getTableName() . "
        ");
        if (!Context::inTestMode()) {
            $db->execute("
                ALTER TABLE " . $newTableView->getTableName() . " ADD PRIMARY KEY (id)
            ");
#todo: copy all other indexes on the old table
        }
    }

    public function createBranch(Branch $branch)
    {
        $db = Context::getDB();

        // copy the branch views of the mother branch into the new branch
        $db->execute("
            INSERT INTO indra_branch_view (branch_id, type_id, view_id)
            SELECT '" . $db->esc($branch->getBranchId()) . "', `type_id`, `view_id`
            FROM indra_branch_view
            WHERE branch_id = '" . $db->esc($branch->getMotherBranchId()) . "'
        ");
    }

    public function getCommit($branchId, $commitIndex)
    {
        $db = Context::getDB();

        $data = $db->querySingleRow("
            SELECT * FROM`indra_commit`
              WHERE
                  `branch_id` = '" . $branchId . "' AND
                  `commit_index` = '" . $commitIndex . "'
        ");

        $commit = new Commit($branchId, $commitIndex, $data['reason'], $data['username'], $data['datetime'], $data['merge_branch_id'], $data['merge_commit_index']);

        return $commit;
    }

    private function getMySqlDataType($dataType)
    {
        return ($dataType == 'varchar') ? 'varchar(255)' : $dataType;
    }

    private function createValueClause(array $attributeValues)
    {
        $values = "";
        $db = Context::getDB();

        foreach ($attributeValues as $attributeId => list($oldValue, $newValue)) {

            $values .= $values ? ", " : "";
            $values .= "`" . $attributeId . "` = '" . $db->esc($newValue) . "'";
        }

        return $values;
    }
}
