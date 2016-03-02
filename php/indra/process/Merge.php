<?php

namespace indra\process;

use indra\storage\Branch;
use indra\storage\Commit;

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
        // Special case: no source = target
        if ($source->getBranchId() == $target->getBranchId()) {
            return null;
        }

        // create commit and update branch
        $mergeCommit = $this->createCommit($target, $commitDescription, $source->getCommitId());

        // execute the merge commit on the branch view
        $this->performCommitOnBranchViews($target, $mergeCommit);

        return $mergeCommit;
    }
}