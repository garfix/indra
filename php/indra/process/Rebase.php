<?php

namespace indra\process;

use indra\service\Context;
use indra\storage\Branch;

/**
 * @author Patrick van Bergen
 */
class Rebase extends VersionControlProcess
{
    /**
     * Rebase means: change the base of a set of commits.
     * The set of commits is the set from the branching point of $source and $target up to the head of $source.
     * The new base is the last commit of $source.
     *
     * @param Branch $target
     * @param Branch $source
     */
    public function run(Branch $target, Branch $source)
    {
        $persistenceStore = Context::getPersistenceStore();

        // special case: source = target
        if ($source->getBranchId() == $target->getBranchId()) {
            return;
        }

        // find the commits since source split off
        $newCommits = $this->findDivergingCommits($source, $target);

        // change the mother branch of $target
        $target->setMotherBranchId($source->getBranchId());
        $persistenceStore->storeBranch($target);

        // update the commits
    }
}