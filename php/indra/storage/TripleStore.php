<?php

namespace indra\storage;

use Exception;
use indra\exception\DataBaseException;
use indra\object\Object;

/**
 * @author Patrick van Bergen
 */
interface TripleStore
{
    /**
     * @return void
     * @throws DataBaseException
     */
    public function createBasicTables();

    /**
     * @param Object $object
     * @return void
     */
    public function load(Object $object);

    /**
     * @param Object $object
     * @return void
     * @throws Exception
     */
    public function save(Object $object);

    /**
     * @param Object $object
     * @return void
     */
    public function remove(Object $object);

    /**
     * @param Revision $revision
     * @throws DataBaseException
     */
    public function storeRevision(Revision $revision);

    /**
     * Perform $undoRevision to revert $revision.
     *
     * @param Revision $revision
     * @param Revision $undoRevision
     */
    public function revertRevision(Revision $revision, Revision $undoRevision);
}