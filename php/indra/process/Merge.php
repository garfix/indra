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

        // find the commits since source split off
        $sourceCommits = $this->findDivergingCommits($target, $source);

        foreach ($sourceCommits as $sourceCommit) {
            foreach ($persistenceStore->getDomainObjectTypeCommits($sourceCommit) as $dotCommit) {

                // update the branch view
                $branchView = $persistenceStore->getBranchView($target->getBranchId(), $dotCommit->getTypeId());
                foreach ($dotCommit->getDiffItems() as $diffItem) {
                    $persistenceStore->processDiffItem($branchView, $diffItem);
                }

                // add the diffs of the commit
                $newDotCommit = new DomainObjectTypeCommit($target->getCommitId(), $dotCommit->getTypeId(), $dotCommit->getDiffItems());
                $persistenceStore->storeDomainObjectTypeCommit($newDotCommit);
            }
        }

        return $mergeCommit;
    }
}