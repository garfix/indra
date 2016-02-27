<?php

namespace indra\process;

use indra\service\Context;
use indra\storage\Branch;
use indra\storage\Commit;
use indra\storage\DomainObjectTypeCommit;

/**
 * @author Patrick van Bergen
 */
class Merge extends VersionControlProcess
{
    /**
     * @param Branch $target
     * @param Branch $source
     * @param string $commitDescription
     * @return Commit|null
     */
    public function run(Branch $target, Branch $source, $commitDescription)
    {
        $persistenceStore = Context::getPersistenceStore();

        // Special case: no source = target
        if ($source->getBranchId() == $target->getBranchId()) {
            return null;
        }

        // create commit and update branch
        $mergeCommit = $this->createCommit($target, $commitDescription);

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
}