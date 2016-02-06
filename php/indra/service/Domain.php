<?php

namespace indra\service;

use indra\diff\AttributeValueChanged;
use indra\object\DomainObject;
use indra\storage\BaseRevision;
use indra\storage\Branch;
use indra\storage\Commit;
use indra\storage\DomainObjectTypeCommit;
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

    /** @var Branch */
    private $activeBranch = null;

    /** @var  Revision */
    private $activeRevision = null;

    /** @var DomainObject[] */
    private $saveList = [];

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
    public function startNewBranch(Commit $motherCommit)
    {
        $this->activeBranch = new Branch(Context::getIdGenerator()->generateId(), $motherCommit->getBranchId(), $motherCommit->getCommitIndex());

        return $this->activeBranch;
    }

    /**
     * @param Branch $branch
     */
    public function startBranch(Branch $branch)
    {
        $this->activeBranch = $branch;
    }

    public function getMasterBranch()
    {
        $tripleStore = Context::getTripleStore();

        $branch = $tripleStore->loadBranch(Branch::MASTER);

        if (!$branch) {
            $branch = new Branch(Branch::MASTER, null, null);
            $branch->setActiveRevision(new BaseRevision());
        }

        return $branch;
    }

    /**
     * @return Branch
     */
    public function getActiveBranch()
    {
        return $this->activeBranch ?: $this->activeBranch = $this->getMasterBranch();
    }

    public function addToSaveList(DomainObject $Object)
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

        $branch->increaseCommitIndex();

        $revision = new Revision(Context::getIdGenerator()->generateId());
        $revision->setSourceRevision($this->getActiveRevision());
        $revision->setDescription($commitDescription);

        // store the revision
        $tripleStore->storeRevision($revision);

        $dateTime = Context::getDateTimeGenerator()->getDateTime();
        $userName = Context::getUserNameProvider()->getUserName();

        $commit = new Commit($branch->getBranchId(), $branch->getCommitIndex(), $commitDescription, $userName, $dateTime->format('Y-m-d H:i:s'));

        // store the commit
        $tripleStore->storeCommit($commit);

        // link current branch to new revision
        $branch->setActiveRevision($revision);

        // store the branch
        $tripleStore->saveBranch($branch);

        // add the changes to the revision
        foreach ($this->saveList as $object) {
            $tripleStore->save($object, $revision, $this->getActiveBranch());
            $this->getViewStore()->updateView($object);
        }

        $this->storeDiffs($branch->getBranchId(), $branch->getCommitIndex());

        $this->saveList = [];

        $this->activeRevision = $revision;

#todo only commit
        return [$revision, $commit];
    }

    public function storeDiffs($branchId, $commitIndex)
    {
        $tripleStore = Context::getTripleStore();

#todo this must be much improved
# do not store what is deleted, etc

        $objectTypeDiff = [];

        foreach ($this->saveList as $object) {

            $typeId = $object->getType()->getId();

            foreach ($object->getChangedAttributeValues() as $attributeTypeId => list($oldValue, $newValue)) {

                $objectTypeDiff[$typeId][] = new AttributeValueChanged($object->getId(), $attributeTypeId, $newValue, $oldValue);
            }
        }

        foreach ($objectTypeDiff as $typeId => $diffItems) {

            $dotCommit = new DomainObjectTypeCommit($branchId, $typeId, $commitIndex, $diffItems);

            $tripleStore->storeDomainObjectTypeCommit($dotCommit);

        }
    }

    /**
     * @param Branch $source
     * @param Branch $target
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