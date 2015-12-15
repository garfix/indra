<?php

namespace indra\service;

use indra\object\Object;
use indra\storage\BaseRevision;
use indra\storage\Branch;
use indra\storage\MasterBranch;
use indra\storage\MySqlViewStore;
use indra\storage\Revision;
use indra\storage\ViewStore;

/**
 * @author Patrick van Bergen
 */
class Domain
{

    /** @var  ViewStore */
    private $viewStore;

//    /** @var  RevisionModel */
//    private $RevisionModel;

    /** @var Branch */
    private $activeBranch = null;

    /** @var  BranchModel */
    private $BranchModel;

    /** @var  Revision */
    private $activeRevision = null;

    /** @var bool  */
    private $useRevisions = false;

    /** @var Object[] */
    private $saveList = [];

    public static function loadFromIni()
    {
#todo: load from ini
        return new Domain();
    }

    public static function loadFromSettings($useRevisions = false)
    {
        return new Domain($useRevisions);
    }

    public function __construct($useRevisions = false)
    {
        $this->useRevisions = $useRevisions;
    }

    public function usesRevisions()
    {
        return $this->useRevisions;
    }

    /**
     * @param ViewStore $viewStore
     */
    public function setViewStore(ViewStore $viewStore)
    {
        $this->viewStore = $viewStore;
    }

    /**
     * @return MySqlViewStore|ViewStore
     */
    public function getViewStore()
    {
        return $this->viewStore ?: $this->viewStore = new MySqlViewStore();
    }

    /**
     * @return Branch
     */
    public function startNewBranch()
    {
        $this->activeBranch = new Branch();

        return $this->activeBranch;
    }

    /**
     * @param Branch $branch
     */
    public function startBranch(Branch $branch)
    {
        $this->activeBranch = $branch;
    }

    /**
     * @return MasterBranch
     */
    public function getActiveBranch()
    {
        return $this->activeBranch ?: $this->activeBranch = new MasterBranch();
    }

    public function addToSaveList(Object $Object)
    {
        $this->saveList[] = $Object;
    }

    private function createRevision($description)
    {
        $revision = new Revision(Context::getIdGenerator()->generateId());
        $revision->setSourceRevision($this->getActiveRevision());
        $revision->setDescription($description);

        $this->activeRevision = $revision;

        return $revision;
    }

    /**
     * @return Revision
     */
    public function getActiveRevision()
    {
        return $this->activeRevision ?: $this->activeRevision = new BaseRevision();
    }

    public function commit($commitDescription)
    {
        $tripleStore = Context::getTripleStore();
        $branch = $this->getActiveBranch();

        $revision = new Revision(Context::getIdGenerator()->generateId());
        $revision->setSourceRevision($this->getActiveRevision());
        $revision->setDescription($commitDescription);

        // store the revision
        $tripleStore->storeRevision($revision);

        // link current branch to new revision
        $branch->setActiveRevision($revision);

        // store the branch
        $tripleStore->saveBranch($branch);

        // add the changes to the revision
        foreach ($this->saveList as $object) {
            $tripleStore->save($object, $revision, $this->getActiveBranch());
            $this->getViewStore()->updateView($object);
        }

        $this->saveList = [];

        $this->activeRevision = $revision;

        return $revision;
    }

    /**
     * @param Branch $source
     */
    public function mergeBranch(Branch $source, Branch $target)
    {
        $tripleStore = Context::getTripleStore();

        // find all revisions of $branch after the common revision
        $revisionIds = $this->findMergeableRevisions($target, $source);

        // apply these revisions to the other branch
        $mergeRevision = new Revision(Context::getIdGenerator()->generateId());
        $mergeRevision->setSourceRevision($target->getActiveRevision());
        $target->setActiveRevision($mergeRevision);
        $tripleStore->mergeRevisions($source, $target, $mergeRevision, $revisionIds);
        $tripleStore->saveBranch($target);
    }

    private function findMergeableRevisions(Branch $branch1, Branch $branch2)
    {
        $tripleStore = Context::getTripleStore();

        $branch1Revisions = [];
        $branch2Revisions = [];

        $branch1RevisionId = $branch1->getActiveRevision()->getId();
        $branch2RevisionId = $branch2->getActiveRevision()->getId();

        do {

            $branch1Revisions[] = $branch1RevisionId;
            $branch2Revisions[] = $branch2RevisionId;

            $branch1Source = $tripleStore->getSourceRevisionId($branch1RevisionId);
            $branch2Source = $tripleStore->getSourceRevisionId($branch2RevisionId);

//var_dump($branch1Source);
//var_dump($branch2Source);

            // common revision found!
            if (in_array($branch2Source, $branch1Revisions)) {
                break;
            }
            if (in_array($branch1Source, $branch2Revisions)) {
                break;
            }

            $branch1RevisionId = $branch1Source;
            $branch2RevisionId = $branch2Source;

        } while ($branch1RevisionId != BaseRevision::ID);

        return array_reverse($branch2Revisions);
    }

    /**
     * Undoes all actions of $revision.
     *
     * @param Revision $revision
     * @return Revision The undo revision
     */
    public function revertRevision(Revision $revision)
    {
        $tripleStore = Context::getTripleStore();

        $undoRevision = $this->createRevision(sprintf("Undo revision %s (%s)",
            $revision->getId(), $revision->getDescription()));

        $tripleStore->revertRevision($this->getActiveBranch(), $revision, $undoRevision);

        return $undoRevision;
    }
}