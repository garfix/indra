<?php

namespace indra\service;

use indra\storage\Revision;
use indra\storage\RevisionAction;

/**
 * @author Patrick van Bergen
 */
class RevisionModel
{
    /**
     * @param string $description
     * @return Revision
     */
    public function createRevision($description)
    {
        return new Revision($description);
    }

    /**
     * @param Revision $revision
     */
    public function saveRevision(Revision $revision)
    {
        $tripleStore = Context::getTripleStore();

        $tripleStore->storeRevision($revision);

        foreach ($revision->getSaveList() as $object) {
            $tripleStore->save($object, $revision->getId());
        }
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

        $tripleStore->revertRevision($revision, $undoRevision);

        return $undoRevision;
    }
}