<?php

namespace indra\service;

use indra\diff\AttributeValuesChanged;
use indra\diff\DiffItem;
use indra\diff\ObjectAdded;
use indra\object\DomainObject;
use indra\object\Type;
use indra\storage\Branch;
use indra\storage\BranchView;
use indra\storage\Commit;
use indra\storage\DiffService;
use indra\storage\DomainObjectTypeCommit;
use indra\storage\Snapshot;
use indra\storage\TableView;

/**
 * @author Patrick van Bergen
 */
class Domain
{
    /** @var Branch|null */
    private $activeBranch = null;

    /** @var Commit|null */
    private $activeCommit = null;

    /** @var DomainObject[] */
    private $saveList = [];

    /** @var DomainObject[] */
    private $removeList = [];

    /**
     * Create a new branch and make this the active branch. New commits will be done in this branch.
     *
     * @return Branch
     */
    public function checkoutNewBranch()
    {
        $existingBranch = $this->getActiveBranch();

        $newBranch = new Branch(Context::getIdGenerator()->generateId(), $existingBranch->getBranchId(), $existingBranch->getCommitIndex());
        Context::getPersistenceStore()->createBranch($newBranch);

        $this->checkoutBranch($newBranch);
        return $newBranch;
    }

    /**
     * Make $branch the active branch. New commits will be done in this branch.
     *
     * @param Branch $branch
     */
    public function checkoutBranch(Branch $branch)
    {
        $this->activeBranch = $branch;
        $this->activeCommit = null;
    }

    /**
     * @param $branchId
     * @return Branch|null
     */
    public function getBranchById($branchId)
    {
        $persistenceStore = Context::getPersistenceStore();

        return $persistenceStore->loadBranch($branchId);
    }

    /**
     * @return Branch
     */
    public function getMasterBranch()
    {
        $persistenceStore = Context::getPersistenceStore();

        $branch = $persistenceStore->loadBranch(Branch::MASTER);

        if (!$branch) {
            $branch = new Branch(Branch::MASTER, null, null);
        }

        return $branch;
    }

    /**
     * @return Branch
     */
    private function getActiveBranch()
    {
        return $this->activeBranch ?: $this->activeBranch = $this->getMasterBranch();
    }

    /**
     * @param string $branchId Indra-id
     * @param int $commitIndex
     * @return Commit
     */
    public function getCommitById($branchId, $commitIndex)
    {
        return Context::getPersistenceStore()->getCommit($branchId, $commitIndex);
    }

    /**
     * Not to be used by application code.
     *
     * @param DomainObject $Object
     */
    public function _addToSaveList(DomainObject $Object)
    {
        $this->saveList[] = $Object;
    }

    /**
     * Not to be used by application code.
     *
     * @param DomainObject $Object
     */
    public function _addToRemoveList(DomainObject $object)
    {
        $this->removeList[] = $object;
#todo: this is not the place
        Context::getPersistenceStore()->remove($object, $this->getActiveBranch());
    }

    /**
     * Not to be used by application code.
     *
     * @param Type $type
     * @param $objectId
     * @return array|null
     */
    public function _loadDomainObjectAttributes(Type $type, $objectId)
    {
        return Context::getPersistenceStore()->loadAttributes($objectId, $this->getActiveView($type));
    }

    /**
     * @param string $commitDescription
     * @return Commit
     */
    public function commit($commitDescription)
    {
        $persistenceStore = Context::getPersistenceStore();
        $branch = $this->getActiveBranch();

        $branch->increaseCommitIndex();

        $dateTime = Context::getDateTimeGenerator()->getDateTime();
        $userName = Context::getUserNameProvider()->getUserName();

        // create a new commit
        $commit = new Commit($branch->getBranchId(), $branch->getCommitIndex(), $commitDescription, $userName, $dateTime->format('Y-m-d H:i:s'));

        // store the commit
        $persistenceStore->storeCommit($commit);

        // store the branch
        $persistenceStore->storeBranch($branch);

        $this->storeDiffs($branch, $branch->getCommitIndex());

        foreach ($this->saveList as $object) {
            $object->markAsSaved();
        }

        $this->saveList = [];

        return $commit;
    }

    /**
     * @param Branch $branch
     * @param int $commitIndex
     */
    private function storeDiffs(Branch $branch, $commitIndex)
    {
        $persistenceStore = Context::getPersistenceStore();
        $branchId = $branch->getBranchId();

#todo this must be much improved
# do not store what is deleted, check if an object is first created, updated, then deleted, etc.

        $objectTypeDiff = [];
        $types = [];

        foreach ($this->saveList as $object) {

            $typeId = $object->getType()->getId();

            $types[$typeId] = $object->getType();

            $changedValues = $object->getChangedAttributeValues();

            if ($object->isNew()) {

                // add / update object (the situation is handled in the database class)
                $objectTypeDiff[$typeId][] = new ObjectAdded($object->getId(), $changedValues);

            } elseif ($changedValues) {

                $objectTypeDiff[$typeId][] = new AttributeValuesChanged($object->getId(), $changedValues);

            }
        }

        foreach ($objectTypeDiff as $typeId => $diffItems) {

            $dotCommit = new DomainObjectTypeCommit($branchId, $typeId, $commitIndex, $diffItems);

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

    /**
     * @param Branch $source
     * @param string $commitDescription
     * @return Commit
     */
    public function mergeBranch(Branch $source, $commitDescription)
    {
        $persistenceStore = Context::getPersistenceStore();

        $target = $this->getActiveBranch();

        // Special case: no source = target
        if ($source->getBranchId() == $target->getBranchId()) {
            return null;
        }

        $target->increaseCommitIndex();

        $dateTime = Context::getDateTimeGenerator()->getDateTime();
        $userName = Context::getUserNameProvider()->getUserName();

        // create a new commit
        $mergeCommit = new Commit($target->getBranchId(), $target->getCommitIndex(), $commitDescription, $userName, $dateTime->format('Y-m-d H:i:s'));

        // store the commit
        $persistenceStore->storeCommit($mergeCommit);

        // store the branch
        $persistenceStore->storeBranch($target);

        $sourceCommits = $this->findMergeableCommits($target, $source);

        foreach ($sourceCommits as $sourceCommit) {
            foreach ($persistenceStore->getDomainObjectTypeCommits($sourceCommit) as $dotCommit) {

                // update the branch view
                $branchView = $persistenceStore->getBranchView($target->getBranchId(), $dotCommit->getTypeId());
                foreach ($dotCommit->getDiffItems() as $diffItem) {
                    $persistenceStore->processDiffItem($branchView, $diffItem);
                }

                // add the diffs of the commit
                $newDotCommit = new DomainObjectTypeCommit($target->getBranchId(), $dotCommit->getTypeId(), $mergeCommit->getCommitIndex(), $dotCommit->getDiffItems());
                $persistenceStore->storeDomainObjectTypeCommit($newDotCommit);
            }
        }

        return $mergeCommit;
    }

    /**
     * @param Branch $target
     * @param Branch $source
     * @return Commit[]
     * @throws \Exception
     */
    private function findMergeableCommits(Branch $target, Branch $source)
    {
        $persistenceStore = Context::getPersistenceStore();

        /** @var Branch[] $parentBranches */
        list($parentBranches, $finalCommitId) = $this->findMergeableBranchList($target, $source);

        $commits = [];

        $lastCommitIndex = $parentBranches[0]->getCommitIndex();
        $lastBranchIndex = (count($parentBranches) - 1);

        foreach ($parentBranches as $p => $parentBranch) {

            $isLastBranch = ($p == $lastBranchIndex);

            if ($isLastBranch) {
                // the final commit itself should not be reached
                $firstCommitIndex = $finalCommitId + 1;
            } else {
                $firstCommitIndex = 1;
            }

            for ($i = $lastCommitIndex; $i >= $firstCommitIndex; $i--) {
                $commits[] = $persistenceStore->getCommit($parentBranch->getBranchId(), $i);
            }

            $lastCommitIndex = $parentBranch->getMotherCommitIndex();
        }

        $commits = array_reverse($commits);

        return $commits;
    }

    /**
     * @param Branch $target
     * @param Branch $source
     * @return array [Branch[], int]
     * @throws \Exception
     */
    private function findMergeableBranchList(Branch $target, Branch $source)
    {
        $persistenceStore = Context::getPersistenceStore();

        /** @var Branch[] $sourceParents */
        $sourceParents = [$source];
        /** @var Branch[] $targetParents */
        $targetParents = [$target];

        $sourceParentIds = [$source->getBranchId()];
        $targetParentIds = [$target->getBranchId()];

        while ($target->getMotherBranchId() || $source->getMotherBranchId()) {

            // repeat until both the source route and the target route have reached a common branch
            if (in_array($source->getBranchId(), $targetParentIds) && in_array($target->getBranchId(), $sourceParentIds)) {
                break;
            }

            // take the next branches up
            if ($target->getMotherBranchId()) {
                $target = $persistenceStore->loadBranch($target->getMotherBranchId());
                $targetParents[] = $target;
                $targetParentIds[] = $target->getBranchId();
            }

            if ($source->getMotherBranchId()) {
                $source = $persistenceStore->loadBranch($source->getMotherBranchId());
                $sourceParents[] = $source;
                $sourceParentIds[] = $source->getBranchId();
            }
        }

        $finalSourceCommitId = null;
        $finalTargetCommitId = null;

        $check1 = false;
        $check2 = false;

        // go up the source path until the target path is reached, and collect the branches
        $mergeableBranchList = [];
        foreach ($sourceParents as $sourceParent) {

            // collect
            $mergeableBranchList[] = $sourceParent;

            // has the source path reached the target path?
            if (in_array($sourceParent->getBranchId(), $targetParentIds)) {
                $check1 = true;
                break;
            } else {
                // no: move the final commit up
                $finalSourceCommitId = $sourceParent->getMotherCommitIndex();
            }
        }

        $finalTargetCommitId = null;
        foreach ($targetParents as $targetParent) {
            // has the target path reached the source path?
            if (in_array($targetParent->getBranchId(), $sourceParentIds)) {
                $check2 = true;
                break;
            } else {
                // no: move the final commit up
                $finalTargetCommitId = $targetParent->getMotherCommitIndex();
            }
        }

        if (!$check1 || !$check2) {
            throw new \Exception('Check failed');
        }

        // the merge should go up to the first commit where the paths split
        if ($finalTargetCommitId !== null) {
            $finalCommitId = min($finalSourceCommitId, $finalTargetCommitId);
        } else {
            $finalCommitId = $finalSourceCommitId;
        }

        return [$mergeableBranchList, $finalCommitId];
    }

    /**
     * Undoes all diffs of $commit
     *
     * @param Commit $commit
     * @return Commit The undo commit
     */
    public function revertCommit(Commit $commit)
    {
        $persistenceStore = Context::getPersistenceStore();

        $branch = Context::getPersistenceStore()->loadBranch($commit->getBranchId());
        $branch->increaseCommitIndex();

        $diffService = new DiffService();

        $reason = sprintf("Undo commit %s (%s)", $commit->getCommitIndex(), $commit->getReason());
        $userName = Context::getUserNameProvider()->getUserName();
        $dateTime = Context::getDateTimeGenerator()->getDateTime();

        $undoCommit = new Commit($branch->getBranchId(), $branch->getCommitIndex(), $reason, $userName, $dateTime->format('Y-m-d H:i:s'), null, null);
        $persistenceStore->storeCommit($undoCommit);

        // store the branch
        $persistenceStore->storeBranch($branch);

        foreach ($persistenceStore->getDomainObjectTypeCommits($commit) as $domainObjectTypeCommit) {

            $typeId = $domainObjectTypeCommit->getTypeId();

            $reversedDiffItems = [];

            foreach (array_reverse($domainObjectTypeCommit->getDiffItems()) as $diffItem) {
                $reversedDiffItems[] = $diffService->getReverseDiffItem($diffItem);
            }

            $dotCommit = new DomainObjectTypeCommit($commit->getBranchId(), $typeId, $branch->getCommitIndex(), $reversedDiffItems);

            $persistenceStore->storeDomainObjectTypeCommit($dotCommit);

            $branchView = $persistenceStore->getBranchView($branch->getBranchId(), $domainObjectTypeCommit->getTypeId());

            foreach ($reversedDiffItems as $diffItem) {
                $persistenceStore->processDiffItem($branchView, $diffItem);
            }
        }

        return $undoCommit;
    }

    /**
     * @param Type $type
     * @return TableView
     */
    public function getActiveView(Type $type)
    {
        if ($this->activeCommit && $this->activeCommit->getCommitIndex() != $this->getActiveBranch()->getCommitIndex()) {
            return $this->getSnapshot($this->activeCommit, $type);
        } else {
            return Context::getPersistenceStore()->getBranchView($this->getActiveBranch()->getBranchId(), $type->getId());
        }
    }

    public function getSnapshot(Commit $commit, Type $type)
    {
        $snapshot = Context::getPersistenceStore()->loadSnapshot($commit, $type->getId());
        if (!$snapshot) {
            $snapshot =  $this->createSnapshot($commit, $type);
        }
        return $snapshot;
    }

    public function createSnapshot(Commit $commit, Type $type)
    {
        $diffService = new DiffService();
        $persistenceStore = Context::getPersistenceStore();

        $snapshot = new Snapshot($commit->getBranchId(), $commit->getCommitIndex(), $type->getId(), Context::getIdGenerator()->generateId());
        $persistenceStore->storeSnapshot($snapshot, $persistenceStore->getBranchView($this->getActiveBranch()->getBranchId(), $type->getId()));

        $branch = $this->activeBranch;

        for ($i = $branch->getCommitIndex(); $i > $commit->getCommitIndex(); $i--) {

            $inBetweenCommit = Context::getPersistenceStore()->getCommit($branch->getBranchId(), $i);

            foreach (Context::getPersistenceStore()->getDomainObjectTypeCommitsForType($inBetweenCommit, $type) as $domainObjectTypeCommit) {

                $reversedDiffItems = [];

                foreach (array_reverse($domainObjectTypeCommit->getDiffItems()) as $diffItem) {
                    $reversedDiffItems[] = $diffService->getReverseDiffItem($diffItem);
                }

                foreach ($reversedDiffItems as $diffItem) {
                    $persistenceStore->processDiffItem($snapshot, $diffItem);
                }
            }
        }

        return $snapshot;
    }

    public function checkoutCommit(Commit $commit)
    {
        $this->activeCommit = $commit;

        // switch to the branch of the commit
        if ($this->activeBranch->getBranchId() != $commit->getBranchId()) {
            $this->activeBranch = Context::getPersistenceStore()->loadBranch($commit->getBranchId());
        }
    }
}