<?php

namespace indra\process;

use indra\service\Context;
use indra\storage\Branch;
use indra\storage\Commit;

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
        $divergingCommits = $this->findDivergingCommits($source, $target);

        // rebase the diverging commits to the head of the source branch
        if (!empty($divergingCommits)) {
            /** @var Commit $firstCommit */
            $firstCommit = reset($divergingCommits);
            $firstCommit->setMotherCommitId($source->getCommitId());
            $persistenceStore->updateMotherCommitId($firstCommit);
        }

        // target drops its old views and copies source's views
        $persistenceStore->removeBranchViews($target);
        $persistenceStore->copyBranchViews($source, $target);

        // the divergent commits are played on top of the new branch
        $this->performCommitsOnBranchViews($target, $divergingCommits);
    }
}