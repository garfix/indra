<?php

namespace indra\process;

use indra\diff\BranchMerged;
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

        // collect all commits involved in the merge, per type
        $typeIds = [];
        foreach ($this->findDivergingCommits($target->getHeadCommitId(), $source->getHeadCommitId()) as $commit) {
            foreach ($persistenceStore->loadDomainObjectTypeCommits($commit) as $dotCommit) {
                $typeIds[$dotCommit->getTypeId()][] = $dotCommit->getCommitId();
            }
        }

        // create commit and update branch
        $mergeCommit = $this->createCommit($target, $commitDescription, $source->getHeadCommitId());

        // create a dot commit for each type involved in the merge
        foreach ($typeIds as $typeId => $commitIds) {
            $diffItem = new BranchMerged($commitIds);
            $newDotCommit = new DomainObjectTypeCommit($mergeCommit->getCommitId(), $typeId, [$diffItem]);
            $persistenceStore->storeDomainObjectTypeCommit($newDotCommit);
        }

        // execute the merge commit on the branch view
        $this->performCommitOnBranchViews($target, $mergeCommit);

        return $mergeCommit;
    }
}