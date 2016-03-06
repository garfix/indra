<?php

namespace indra\process;

use indra\object\Type;
use indra\service\Context;
use indra\storage\Branch;
use indra\storage\Commit;
use indra\storage\DiffService;
use indra\storage\TableView;

/**
 * @author Patrick van Bergen
 */
abstract class VersionControlProcess
{
    /**
     * @param Branch $branch
     * @param $commitDescription
     * @param $fatherCommitId
     * @return Commit
     */
    protected function createCommit(Branch $branch, $commitDescription, $fatherCommitId = null)
    {
        $persistenceStore = Context::getPersistenceStore();

        $commitId = Context::getIdGenerator()->generateId();
        $motherCommitId = $branch->getHeadCommitId();
        $dateTime = Context::getDateTimeGenerator()->getDateTime();
        $userName = Context::getUserNameProvider()->getUserName();

        // create a new commit
        $commit = new Commit($commitId, $motherCommitId, $commitDescription, $userName, $dateTime->format('Y-m-d H:i:s'), $fatherCommitId);
        $persistenceStore->storeCommit($commit);

        // update the branch
        $branch->setHeadCommitId($commitId);
        $persistenceStore->storeBranch($branch);

        return $commit;
    }

    /**
     * @param $baseHeadCommitId
     * @param $divergentHeadCommitId
     * @return \indra\storage\Commit[]
     */
    protected function findDivergingCommits($baseHeadCommitId, $divergentHeadCommitId)
    {
        $persistenceStore = Context::getPersistenceStore();

        /** @var Commit[] $baseCommits */
        $baseCommits = [];
        /** @var Commit[] $divergentCommits */
        $divergentCommits = [];

        $fatherCommitIds = [];

        $baseCommitId = $baseHeadCommitId;
        $divergentCommitId = $divergentHeadCommitId;

        $commonCommitId = null;

        do {

            if ($baseCommitId != null) {

                $baseCommit = $persistenceStore->loadCommit($baseCommitId);
                $baseCommits[$baseCommitId] = $baseCommit;
                $fatherCommitIds[] = $baseCommit->getFatherCommitId();

                // common commit found?
                if (array_key_exists($baseCommitId, $divergentCommits)) {
                    $commonCommitId = $baseCommitId;
                    break;
                }

                $baseCommitId = $baseCommit->getMotherCommitId();
            }

            if ($divergentCommitId != null) {

                $divergentCommit = $persistenceStore->loadCommit($divergentCommitId);
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
        $commitId = $divergentHeadCommitId;

        while ($commitId != $commonCommitId) {

            if (in_array($commitId, $fatherCommitIds)) {
                break;
            }

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

        foreach ($persistenceStore->loadDomainObjectTypeCommits($commit) as $dotCommit) {

            $branchView = $persistenceStore->loadBranchView($branch->getBranchId(), $dotCommit->getTypeId());

            foreach ($dotCommit->getDiffItems() as $diffItem) {
                $persistenceStore->processDiffItem($branchView, $diffItem);
            }
        }
    }

    /**
     * @param TableView $tableView
     * @param Commit $commit
     * @param Type $type
     */
    protected function performReversedCommitOnTableView(TableView $tableView, Commit $commit, Type $type)
    {
        $persistenceStore = Context::getPersistenceStore();

        $diffService = new DiffService();
        foreach (Context::getPersistenceStore()->loadDomainObjectTypeCommitsForType($commit, $type) as $domainObjectTypeCommit) {

            foreach (array_reverse($domainObjectTypeCommit->getDiffItems()) as $diffItem) {
                $reversedDiffItem = $diffService->getReverseDiffItem($diffItem);
                $persistenceStore->processDiffItem($tableView, $reversedDiffItem);
            }
        }
    }

    /**
     * @param Branch $branch
     * @param Commit $commit
     */
    protected function performReversedCommitOnBranchViews(Branch $branch, Commit $commit)
    {
        $persistenceStore = Context::getPersistenceStore();
        $diffService = new DiffService();

        foreach ($persistenceStore->loadDomainObjectTypeCommits($commit) as $dotCommit) {

            $branchView = $persistenceStore->loadBranchView($branch->getBranchId(), $dotCommit->getTypeId());

            foreach ($dotCommit->getDiffItems() as $diffItem) {
                $persistenceStore->processDiffItem($branchView, $diffService->getReverseDiffItem($diffItem));
            }
        }
    }
}