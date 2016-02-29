<?php

namespace indra\process;

use indra\diff\AttributeValuesChanged;
use indra\diff\DiffItem;
use indra\diff\ObjectAdded;
use indra\diff\ObjectRemoved;
use indra\object\ModelConnection;
use indra\object\Type;
use indra\service\Context;
use indra\storage\Branch;
use indra\storage\BranchView;
use indra\storage\DomainObjectTypeCommit;

/**
 * @author Patrick van Bergen
 */
class CommitStagedChanges extends VersionControlProcess
{
    /**
     * @param Branch $branch
     * @param ModelConnection $modelConnection
     * @param string $commitDescription
     * @return \indra\storage\Commit
     */
    public function run(Branch $branch, ModelConnection $modelConnection, $commitDescription)
    {
        // create commit and update branch
        $commit = $this->createCommit($branch, $commitDescription);

        // store all the staged changes of this commit
        $this->storeChanges($branch, $modelConnection);

        // staged changes have been processed
        $modelConnection->clear();

        return $commit;
    }

    /**
     * @param Branch $branch
     * @param ModelConnection $modelConnection
     */
    private function storeChanges(Branch $branch, ModelConnection $modelConnection)
    {
        $persistenceStore = Context::getPersistenceStore();
        $commitId = $branch->getCommitId();

        $objectTypeDiff = [];
        $types = [];

        foreach ($modelConnection->getSaveList() as $object) {

            $typeId = $object->getType()->getId();
            $types[$typeId] = $object->getType();

            $changedValues = $object->getChangedAttributeValues();

            if ($object->isNew()) {

                // add / update object (the situation is handled in the database class)
                $objectTypeDiff[$typeId][] = new ObjectAdded($object->getId(), $changedValues);

            } elseif ($changedValues) {

                $objectTypeDiff[$typeId][] = new AttributeValuesChanged($object->getId(), $changedValues);

            }

            $object->markAsSaved();
        }

        foreach ($modelConnection->getRemoveList() as $object) {

            $typeId = $object->getType()->getId();
            $types[$typeId] = $object->getType();

            $removedAttributeValues = [];
            foreach ($object->getOriginalAttributeValues() as $attributeId => $value) {
                $removedAttributeValues[$attributeId] = [$value, null];
            }

            $objectTypeDiff[$typeId][] = new ObjectRemoved($object->getId(), $removedAttributeValues);
        }

        // store diffs per object type
        foreach ($objectTypeDiff as $typeId => $diffItems) {

            $dotCommit = new DomainObjectTypeCommit($commitId, $typeId, $diffItems);

            $persistenceStore->storeDomainObjectTypeCommit($dotCommit);

            $this->updateBranchView($branch, $types[$typeId], $diffItems);
        }
    }

    /**
     * @param Branch $branch
     * @param Type $type
     * @param DiffItem[] $diffItems
     */
    private function updateBranchView(Branch $branch, Type $type, array $diffItems)
    {
        $persistenceStore = Context::getPersistenceStore();

        $branchView = $persistenceStore->getBranchView($branch->getBranchId(), $type->getId());

        // if this branch has no view, or if it is used by other branches as well, create a new view
        if (!$branchView) {

            $branchView = new BranchView($branch->getBranchId(), $type->getId(), Context::getIdGenerator()->generateId());
            $persistenceStore->storeBranchView($branchView, $type);

        } elseif ($persistenceStore->getNumberOfBranchesUsingView($branchView) > 1) {

            $newBranchView = new BranchView($branch->getBranchId(), $type->getId(), Context::getIdGenerator()->generateId());
            $persistenceStore->cloneBranchView($newBranchView, $branchView);
            $branchView = $newBranchView;

        }

        foreach ($diffItems as $diffItem) {
            $persistenceStore->processDiffItem($branchView, $diffItem);
        }
    }
}