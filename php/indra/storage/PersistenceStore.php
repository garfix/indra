<?php

namespace indra\storage;

use indra\diff\DiffItem;
use indra\exception\DataBaseException;
use indra\exception\DiffItemClassNotRecognizedException;
use indra\object\DomainObject;
use indra\object\Type;

/**
 * @author Patrick van Bergen
 */
interface PersistenceStore
{
    /**
     * @return void
     * @throws DataBaseException
     */
    public function createBasicTables();

    /**
     * @param string $objectId
     * @param TableView $view
     * @return
     */
    public function loadAttributes($objectId, TableView $view);

    /**
     * @param DomainObject $object
     * @param Branch $branch
     */
    public function remove(DomainObject $object, Branch $branch);

    /**
     * @param Commit $commit
     * @throws DataBaseException
     */
    public function storeCommit(Commit $commit);

    /**
     * @param Branch $branch
     * @return void
     */
    public function storeBranch(Branch $branch);

    /**
     * @param DomainObjectTypeCommit $dotCommit
     * @return void
     * @throws DataBaseException
     */
    public function storeDomainObjectTypeCommit(DomainObjectTypeCommit $dotCommit);

    /**
     * @param Commit $commit
     * @return DomainObjectTypeCommit[]
     * @throws DataBaseException
     * @throws DiffItemClassNotRecognizedException
     */
    public function getDomainObjectTypeCommits(Commit $commit);

    /**
     * @param Commit $commit
     * @param Type $type
     * @return DomainObjectTypeCommit[]
     */
    public function getDomainObjectTypeCommitsForType(Commit $commit, Type $type);

    /**
     * @param BranchView $branchView
     * @return int
     * @throws DataBaseException
     */
    public function getNumberOfBranchesUsingView(BranchView $branchView);

    /**
     * @param string $branchId
     * @param string $typeId
     * @return BranchView
     * @throws DataBaseException
     */
    public function getBranchView($branchId, $typeId);

    /**
     * @param Commit $commit
     * @param string $typeId
     * @return Snapshot
     * @throws DataBaseException
     */
    public function loadSnapshot(Commit $commit, $typeId);

    /**
     * @param BranchView $branchView
     * @param Type $type
     * @return void
     * @throws DataBaseException
     */
    public function storeBranchView(BranchView $branchView, Type $type);

    /**
     * @param Snapshot $snapshot
     * @param BranchView $branchView
     * @return mixed
     */
    public function storeSnapshot(Snapshot $snapshot, BranchView $branchView);

    /**
     * @param TableView $newTableView
     * @param TableView $oldTableView
     * @return void
     * @throws DataBaseException
     */
    public function cloneTableView(TableView $newTableView, TableView $oldTableView);

    /**
     * @param BranchView $tableView
     * @param DiffItem $diffItem
     * @return void
     * @throws DataBaseException
     */
    public function processDiffItem(TableView $tableView, DiffItem $diffItem);

    /**
     * @param Branch $branch
     * @return void
     * @throws DataBaseException
     */
    public function createBranch(Branch $branch);

    /**
     * @param string $branchId
     * @param int $commitIndex
     * @return Commit
     */
    public function getCommit($branchId, $commitIndex);
}