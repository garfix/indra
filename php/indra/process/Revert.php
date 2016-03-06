<?php

namespace indra\process;

use indra\service\Context;
use indra\storage\Branch;
use indra\storage\Commit;
use indra\storage\DiffService;
use indra\storage\DomainObjectTypeCommit;

/**
 * @author Patrick van Bergen
 */
class Revert extends VersionControlProcess
{
    /**
     * Creates a new commit on $branch that undoes / reverts the actions of $commit.
     * Also updates the branch views.
     *
     * @param Branch $branch
     * @param Commit $commit
     * @return Commit
     * @throws \indra\exception\DiffItemClassNotRecognizedException
     */
    public function run(Branch $branch, Commit $commit)
    {
        $persistenceStore = Context::getPersistenceStore();
        $diffService = new DiffService();

        $undoCommit = $this->createCommit($branch, sprintf("Undo commit %s (%s)", $commit->getCommitId(), $commit->getReason()));

        foreach ($persistenceStore->loadDomainObjectTypeCommits($commit) as $domainObjectTypeCommit) {

            $typeId = $domainObjectTypeCommit->getTypeId();

            $reversedDiffItems = [];
            foreach (array_reverse($domainObjectTypeCommit->getDiffItems()) as $diffItem) {
                $reversedDiffItems[] = $diffService->getReverseDiffItem($diffItem);
            }

            $dotCommit = new DomainObjectTypeCommit($undoCommit->getCommitId(), $typeId, $reversedDiffItems);
            $persistenceStore->storeDomainObjectTypeCommit($dotCommit);
        }

        $this->performReversedCommitOnBranchViews($branch, $commit);

        return $undoCommit;
    }
}