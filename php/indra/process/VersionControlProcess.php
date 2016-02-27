<?php

namespace indra\process;

use indra\service\Context;
use indra\storage\Branch;
use indra\storage\Commit;

/**
 * @author Patrick van Bergen
 */
abstract class VersionControlProcess
{
    /**
     * @param Branch $branch
     * @param $commitDescription
     * @return Commit
     */
    protected function createCommit(Branch $branch, $commitDescription)
    {
        $persistenceStore = Context::getPersistenceStore();

        // update the branch
        $branch->increaseCommitIndex();
        $persistenceStore->storeBranch($branch);

        $dateTime = Context::getDateTimeGenerator()->getDateTime();
        $userName = Context::getUserNameProvider()->getUserName();

        // create a new commit
        $commit = new Commit($branch->getBranchId(), $branch->getCommitIndex(), $commitDescription, $userName, $dateTime->format('Y-m-d H:i:s'));

        // store the commit
        $persistenceStore->storeCommit($commit);

        return $commit;
    }

    /**
     * @param Branch $base
     * @param Branch $divergent
     * @return Commit[]
     * @throws \Exception
     */
    protected function findDivergingCommits(Branch $base, Branch $divergent)
    {
        $persistenceStore = Context::getPersistenceStore();

        /** @var Branch[] $parentBranches */
        list($parentBranches, $finalCommitId) = $this->findMergeableBranchList($base, $divergent);

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
}