<?php

namespace indra\storage;

use indra\diff\DiffItem;
use indra\exception\DataBaseException;
use indra\object\DomainObject;
use indra\object\Type;

#todo: dit is geen triplestore meer & de klasse heeft te veel verantwoordelijkheden

/**
 * @author Patrick van Bergen
 */
interface TripleStore
{
    /**
     * @return void
     * @throws DataBaseException
     */
    public function createBasicTables();

    /**
     * @param Type $type
     * @param $objectId
     * @param Branch $branch
     * @return
     */
    public function loadAttributes(Type $type, $objectId, Branch $branch);

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

    public function storeDomainObjectTypeCommit(DomainObjectTypeCommit $dotCommit);

    public function getNumberOfBranchesUsingView(BranchView $branchView);

    public function getBranchView($branchId, $typeId);

    public function storeBranchView(BranchView $branchView, Type $type);

    public function cloneBranchView(BranchView $newBranchView, BranchView $oldBranchView);

    public function processDiffItem(BranchView $branchView, DiffItem $diffItem);

    public function createBranch(Branch $branch);

    /**
     * @param string $branchId
     * @param int $commitIndex
     * @return Commit
     */
    public function getCommit($branchId, $commitIndex);
}