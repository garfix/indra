<?php

namespace indra\service;

use indra\storage\Revision;

/**
 * @author Patrick van Bergen
 */
class RevisionModel
{
//    /** @var  Revision */
//    private $activeRevision = null;

//    /**
//     * @param string $description
//     * @return Revision
//     */
//    public function createRevision($description)
//    {
//        $this->activeRevision = new Revision($description);
//
//        return $this->activeRevision;
//    }
//
//    public function getActiveRevision()
//    {
//        return $this->activeRevision;
//    }

//    /**
//     * @param Revision $revision
//     */
//    public function saveRevision(Revision $revision)
//    {
//        $tripleStore = Context::getTripleStore();
//
//        $tripleStore->storeRevision($revision);
//
//        foreach ($revision->getSaveList() as $object) {
//            $tripleStore->save($object, $revision);
//        }
//    }
//
//    /**
//     * Undoes all actions of $revision.
//     *
//     * @param Revision $revision
//     * @return Revision The undo revision
//     */
//    public function revertRevision(Revision $revision)
//    {
//        $tripleStore = Context::getTripleStore();
//
//        $undoRevision = $this->createRevision(sprintf("Undo revision %s (%s)",
//            $revision->getId(), $revision->getDescription()));
//
//        $tripleStore->revertRevision($revision, $undoRevision);
//
//        return $undoRevision;
//    }
}