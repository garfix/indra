<?php

namespace indra\service;

use indra\diff\AttributeValuesChanged;
use indra\diff\ObjectAdded;
use indra\object\DomainObject;
use indra\object\Type;
use indra\storage\Branch;
use indra\storage\BranchView;
use indra\storage\Commit;
use indra\storage\DomainObjectTypeCommit;

/**
 * @author Patrick van Bergen
 */
class Domain
{
    /** @var Branch */
    private $activeBranch = null;

    /** @var DomainObject[] */
    private $saveList = [];

    /**
     * @return Branch
     */
    public function startNewBranch()
    {
        $existingBranch = $this->getActiveBranch();

        $this->activeBranch = new Branch(Context::getIdGenerator()->generateId(), $existingBranch->getBranchId(), $existingBranch->getCommitIndex());

        Context::getTripleStore()->createBranch($this->activeBranch);

        return $this->activeBranch;
    }

    /**
     * @param Branch $branch
     */
    public function startBranch(Branch $branch)
    {
        $this->activeBranch = $branch;
    }

    /**
     * @param $branchId
     * @return Branch|null
     */
    public function getBranchById($branchId)
    {
        $tripleStore = Context::getTripleStore();

        return $tripleStore->loadBranch($branchId);
    }

    public function getMasterBranch()
    {
        $tripleStore = Context::getTripleStore();

        $branch = $tripleStore->loadBranch(Branch::MASTER);

        if (!$branch) {
            $branch = new Branch(Branch::MASTER, null, null);
        }

        return $branch;
    }

    /**
     * @return Branch
     */
    public function getActiveBranch()
    {
        return $this->activeBranch ?: $this->activeBranch = $this->getMasterBranch();
    }

    public function addToSaveList(DomainObject $Object)
    {
        $this->saveList[] = $Object;
    }

    public function commit($commitDescription)
    {
        $tripleStore = Context::getTripleStore();
        $branch = $this->getActiveBranch();

        $branch->increaseCommitIndex();

        $dateTime = Context::getDateTimeGenerator()->getDateTime();
        $userName = Context::getUserNameProvider()->getUserName();

        // create a new commit
        $commit = new Commit($branch->getBranchId(), $branch->getCommitIndex(), $commitDescription, $userName, $dateTime->format('Y-m-d H:i:s'));

        // store the commit
        $tripleStore->storeCommit($commit);

        // store the branch
        $tripleStore->saveBranch($branch);

        $this->storeDiffs($branch, $branch->getCommitIndex());

        foreach ($this->saveList as $object) {
            $object->markAsSaved();
        }

        $this->saveList = [];

        return $commit;
    }

    private function storeDiffs(Branch $branch, $commitIndex)
    {
        $tripleStore = Context::getTripleStore();
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

            $tripleStore->storeDomainObjectTypeCommit($dotCommit);

            $this->updateBranchView($branch, $types[$typeId], $diffItems);
        }
    }

    private function updateBranchView(Branch $branch, Type $type, array $diffItems)
    {
        $tripleStore = Context::getTripleStore();

        $branchView = $tripleStore->getBranchView($branch->getBranchId(), $type->getId());

        // if this branch has no view, or if it is used by other branches as well, create a new view
        if (!$branchView) {
            $branchView = new BranchView($branch->getBranchId(), $type->getId(), Context::getIdGenerator()->generateId());
            $tripleStore->storeBranchView($branchView, $type);
        } elseif ($tripleStore->getNumberOfBranchesUsingView($branchView) > 1) {
            $newBranchView = new BranchView($branch->getBranchId(), $type->getId(), Context::getIdGenerator()->generateId());
            $tripleStore->cloneBranchView($newBranchView, $branchView);
            $branchView = $newBranchView;
        }

        foreach ($diffItems as $diffItem) {
            $tripleStore->processDiffItem($branchView, $diffItem);
        }
    }

    /**
     * @param Branch $source
     * @param $commitDescription
     * @return Commit
     */
    public function mergeBranch(Branch $source, $commitDescription)
    {
        $tripleStore = Context::getTripleStore();

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
        $tripleStore->storeCommit($mergeCommit);

        // store the branch
        $tripleStore->saveBranch($target);

        $sourceCommits = $this->findMergeableCommits($target, $source);

        foreach ($sourceCommits as $sourceCommit) {
            foreach ($tripleStore->getDomainObjectTypeCommits($sourceCommit) as $dotCommit) {

                // update the branch view
                $branchView = $tripleStore->getBranchView($target->getBranchId(), $dotCommit->getTypeId());
                foreach ($dotCommit->getDiffItems() as $diffItem) {
                    $tripleStore->processDiffItem($branchView, $diffItem);
                }

                // add the diffs of the commit
                $newDotCommit = new DomainObjectTypeCommit($target->getBranchId(), $dotCommit->getTypeId(), $mergeCommit->getCommitIndex(), $dotCommit->getDiffItems());
                $tripleStore->storeDomainObjectTypeCommit($newDotCommit);
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
        $tripleStore = Context::getTripleStore();

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
                $commits[] = $tripleStore->getCommit($parentBranch->getBranchId(), $i);
            }

            $lastCommitIndex = $parentBranch->getMotherCommitIndex();
        }

        $commits = array_reverse($commits);

        return $commits;
    }

    /**
     * @param Branch $target
     * @param Branch $source
     * @return [Branch[], int]
     * @throws \Exception
     */
    private function findMergeableBranchList(Branch $target, Branch $source)
    {
        $tripleStore = Context::getTripleStore();

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
                $target = $tripleStore->loadBranch($target->getMotherBranchId());
                $targetParents[] = $target;
                $targetParentIds[] = $target->getBranchId();
            }

            if ($source->getMotherBranchId()) {
                $source = $tripleStore->loadBranch($source->getMotherBranchId());
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
        $tripleStore = Context::getTripleStore();

        $branch = Context::getTripleStore()->loadBranch($commit->getBranchId());
        $branch->increaseCommitIndex();

        $diffService = new DiffService();

        $reason = sprintf("Undo commit %s (%s)", $commit->getCommitIndex(), $commit->getReason());
        $userName = Context::getUserNameProvider()->getUserName();
        $dateTime = Context::getDateTimeGenerator()->getDateTime();

        $undoCommit = new Commit($branch->getBranchId(), $branch->getCommitIndex(), $reason, $userName, $dateTime->format('Y-m-d H:i:s'), null, null);
        $tripleStore->storeCommit($undoCommit);

        // store the branch
        $tripleStore->saveBranch($branch);

        foreach ($tripleStore->getDomainObjectTypeCommits($commit) as $domainObjectTypeCommit) {

            $typeId = $domainObjectTypeCommit->getTypeId();

            $reversedDiffItems = [];

            foreach (array_reverse($domainObjectTypeCommit->getDiffItems()) as $diffItem) {
                $reversedDiffItems[] = $diffService->getReverseDiffItem($diffItem);
            }

            $dotCommit = new DomainObjectTypeCommit($commit->getBranchId(), $typeId, $branch->getCommitIndex(), $reversedDiffItems);

            $tripleStore->storeDomainObjectTypeCommit($dotCommit);

            $branchView = $tripleStore->getBranchView($branch->getBranchId(), $domainObjectTypeCommit->getTypeId());

            foreach ($reversedDiffItems as $diffItem) {
                $tripleStore->processDiffItem($branchView, $diffItem);
            }
        }

        return $undoCommit;
    }
}