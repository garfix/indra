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

        $commitId = Context::getIdGenerator()->generateId();
        $motherCommitId = $branch->getCommitId();
        $dateTime = Context::getDateTimeGenerator()->getDateTime();
        $userName = Context::getUserNameProvider()->getUserName();

        // create a new commit
        $commit = new Commit($commitId, $motherCommitId, $commitDescription, $userName, $dateTime->format('Y-m-d H:i:s'));
        $persistenceStore->storeCommit($commit);

        // update the branch
        $branch->setCommitId($commitId);
        $persistenceStore->storeBranch($branch);

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

        /** @var Commit[] $baseCommits */
        $baseCommits = [];
        /** @var Commit[] $divergentCommits */
        $divergentCommits = [];

        $baseCommitId = $base->getCommitId();
        $divergentCommitId = $divergent->getCommitId();

        $commonCommitId = null;

        do {

            if ($baseCommitId != null) {

                $baseCommit = $persistenceStore->getCommit($baseCommitId);
                $baseCommits[$baseCommitId] = $baseCommit;

                // common commit found?
                if (array_key_exists($baseCommitId, $divergentCommits)) {
                    $commonCommitId = $baseCommitId;
                    break;
                }

                $baseCommitId = $baseCommit->getMotherCommitId();
            }

            if ($divergentCommitId != null) {

                $divergentCommit = $persistenceStore->getCommit($divergentCommitId);
                $divergentCommits[$divergentCommitId] = $divergentCommit;

                // common commit found?
                if (array_key_exists($divergentCommitId, $baseCommits)) {
                    $commonCommitId = $divergentCommitId;
                    break;
                }

                $divergentCommitId = $divergentCommit->getMotherCommitId();
            }

        } while ($baseCommitId != null || $divergentCommitId != null);

        // create the cleaned up list of divergent commits
        $resultCommits = [];
        $commitId = $divergent->getCommitId();

        while ($commitId != $commonCommitId) {

            $commit = $divergentCommits[$commitId];
            $resultCommits[] = $commit;
            $commitId = $commit->getMotherCommitId();

        }

        return array_reverse($resultCommits);
    }

    /**
     * @param Branch $branch
     * @param Commit[] $commits
     */
    protected function performCommitsOnBranchViews(Branch $branch, array $commits)
    {
        foreach ($commits as $commit) {
            $this->performCommitOnBranchViews($branch, $commit);
        }
    }

    /**
     * @param Branch $branch
     * @param Commit $commit
     */
    protected function performCommitOnBranchViews(Branch $branch, Commit $commit)
    {
        $persistenceStore = Context::getPersistenceStore();

        foreach ($persistenceStore->getDomainObjectTypeCommits($commit) as $dotCommit) {

            $branchView = $persistenceStore->getBranchView($branch->getBranchId(), $dotCommit->getTypeId());

            foreach ($dotCommit->getDiffItems() as $diffItem) {
                $persistenceStore->processDiffItem($branchView, $diffItem);
            }
        }
    }
}