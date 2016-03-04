<?php

namespace indra\service;

use indra\exception\CommitNotAllowedException;
use indra\object\ModelConnection;
use indra\object\Type;
use indra\process\CommitStagedChanges;
use indra\process\Merge;
use indra\process\Rebase;
use indra\process\Revert;
use indra\storage\Branch;
use indra\storage\Commit;
use indra\storage\DiffService;
use indra\storage\Snapshot;
use indra\storage\TableView;

/**
 * @author Patrick van Bergen
 */
class Domain
{
    /** @var Branch|null */
    private $activeBranch = null;

    /** @var Commit|null */
    private $activeCommit = null;

    /** @var ModelConnection */
    private $modelConnection;

    public function __construct()
    {
        $this->modelConnection = new ModelConnection();
    }

    /**
     * Create a new branch and make this the active branch. New commits will be done in this branch.
     *
     * @param $branchName
     * @return Branch
     */
    public function checkoutNewBranch($branchName)
    {
        $motherBranch = $this->getActiveBranch();

        $newBranch = new Branch(Context::getIdGenerator()->generateId(), $branchName);
        $newBranch->setHeadCommitId($motherBranch->getHeadCommitId());
        Context::getPersistenceStore()->copyBranchViews($motherBranch, $newBranch);

        $this->checkoutBranch($newBranch);
        return $newBranch;
    }

    /**
     * Make $branch the active branch. New commits will be done in this branch.
     *
     * @param Branch $branch
     */
    public function checkoutBranch(Branch $branch)
    {
        $this->activeBranch = $branch;
        $this->activeCommit = null;
    }

    /**
     * Checkout out a commit that lies on a given branch.
     *
     * @param Branch $branch
     * @param Commit $commit
     */
    public function checkoutBranchCommit(Branch $branch, Commit $commit)
    {
        $this->activeBranch = $branch;
        $this->activeCommit = $commit;
    }

    public function rebaseBranch(Branch $source)
    {
        $rebase = new Rebase();
        $rebase->run($this->getActiveBranch(), $source);
    }

    /**
     * @param $branchId
     * @return Branch|null
     */
    public function getBranchById($branchId)
    {
        $persistenceStore = Context::getPersistenceStore();

        return $persistenceStore->loadBranch($branchId);
    }

    /**
     * @return Branch
     */
    public function getMasterBranch()
    {
        $persistenceStore = Context::getPersistenceStore();

        $branch = $persistenceStore->loadBranch(Branch::MASTER);

        if (!$branch) {
            $branch = new Branch(Branch::MASTER, "Master");
        }

        return $branch;
    }

    /**
     * @return Branch
     */
    private function getActiveBranch()
    {
        return $this->activeBranch ?: $this->activeBranch = $this->getMasterBranch();
    }

    /**
     * @param $commitId
     * @return Commit
     */
    public function getCommitById($commitId)
    {
        return Context::getPersistenceStore()->loadCommit($commitId);
    }

    /**
     * This function allows models to interact with the Domain. Do not use it in application code.
     *
     * @return ModelConnection
     */
    public function getModelConnection()
    {
        return $this->modelConnection;
    }

    /**
     * @param Type $type
     * @return TableView
     */
    public function getActiveView(Type $type)
    {
        if ($this->activeCommit) {
            return $this->getSnapshot($this->activeBranch, $this->activeCommit, $type);
        } else {
            return Context::getPersistenceStore()->loadBranchView($this->getActiveBranch()->getBranchId(), $type->getId());
        }
    }

    /**
     * @param string $commitDescription
     * @return Commit
     * @throws CommitNotAllowedException
     */
    public function commit($commitDescription)
    {
        if (!$this->allowCommit()) {
            throw CommitNotAllowedException::getOldCommit();
        }

        $process = new CommitStagedChanges();
        $commit = $process->run($this->getActiveBranch(), $this->modelConnection, $commitDescription);
        return $commit;
    }

    /**
     * Returns a list of commits
     *
     * @param $commitId
     * @param int $count
     * @return \indra\storage\Commit[]
     */
    public function getCommitList($commitId, $count = 25)
    {
        $persistenceStore = Context::getPersistenceStore();

        $commits = [];

        while ($commitId) {

            $commit = $persistenceStore->loadCommit($commitId);
            $commits[] = $commit;

            if (count($commits) == $count) {
                break;
            }

            $commitId = $commit->getMotherCommitId();
        }

        return $commits;
    }

    /**
     * @return bool
     */
    private function allowCommit()
    {
        return !$this->activeCommit;
    }

    /**
     * @param Branch $source
     * @param string $commitDescription
     * @return Commit
     */
    public function mergeBranch(Branch $source, $commitDescription)
    {
        $merge = new Merge();
        $mergeCommit = $merge->run($this->getActiveBranch(), $source, $commitDescription);
        return $mergeCommit;
    }

    /**
     * Undoes all diffs of $commit
     *
     * @param Commit $commit
     * @return Commit The undo commit
     */
    public function revertCommit(Commit $commit)
    {
        $revert = new Revert();
        $undoCommit = $revert->run($this->getActiveBranch(), $commit);
        return $undoCommit;
    }

    private function getSnapshot(Branch $branch, Commit $commit, Type $type)
    {
        $snapshot = Context::getPersistenceStore()->loadSnapshot($commit, $type->getId());
        if (!$snapshot) {
            $snapshot =  $this->createSnapshot($branch, $commit, $type);
        }
        return $snapshot;
    }

    private function createSnapshot(Branch $branch, Commit $commit, Type $type)
    {
        $diffService = new DiffService();
        $persistenceStore = Context::getPersistenceStore();

        $snapshot = new Snapshot($commit->getCommitId(), $type->getId(), Context::getIdGenerator()->generateId());
        $persistenceStore->storeSnapshot($snapshot, $persistenceStore->loadBranchView($this->getActiveBranch()->getBranchId(), $type->getId()));

        $commitId = $branch->getHeadCommitId();

        while ($commitId != null && $commitId != $commit->getCommitId()) {

            $inBetweenCommit = Context::getPersistenceStore()->loadCommit($commitId);

            foreach (Context::getPersistenceStore()->loadDomainObjectTypeCommitsForType($inBetweenCommit, $type) as $domainObjectTypeCommit) {

                $reversedDiffItems = [];

                foreach (array_reverse($domainObjectTypeCommit->getDiffItems()) as $diffItem) {
                    $reversedDiffItems[] = $diffService->getReverseDiffItem($diffItem);
                }

                foreach ($reversedDiffItems as $diffItem) {
                    $persistenceStore->processDiffItem($snapshot, $diffItem);
                }
            }

            $commitId = $inBetweenCommit->getMotherCommitId();
        }

        return $snapshot;
    }

    public function removeAllSnapshots()
    {
        Context::getPersistenceStore()->removeAllSnapshots();
    }
}