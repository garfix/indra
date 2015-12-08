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
     * @param Object|Object $object
     * @param Branch $branch
     */
    public function load(Object $object, Branch $branch);

    /**
     * @param Object|Object $object
     * @param Revision $revision
     * @param Branch $branch
     * @return
     */
    public function save(Object $object, Revision $revision, Branch $branch);

    /**
     * @param Object|Object $object
     * @param Branch $branch
     */
    public function remove(Object $object, Branch $branch);

    /**
     * @param Revision $revision
     * @throws DataBaseException
     */
    public function storeRevision(Revision $revision);

    public function getSourceRevisionId($revisionId);

    /**
     * Perform $undoRevision to revert $revision.
     *
     * @param Revision $revision
     * @param Revision $undoRevision
     */
    public function revertRevision(Branch $branch, Revision $revision, Revision $undoRevision);

    public function mergeRevisions(Branch $fromBranch, Branch $toBranch, Revision $mergeRevision, $revisionIds);
}