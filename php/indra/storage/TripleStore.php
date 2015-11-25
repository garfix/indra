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
     * @param Revision $revision
     * @throws DataBaseException
     */
    public function storeRevision(Revision $revision);

    /**
     * @param Object $object
     * @param $objectId
     * @return mixed
     */
    public function load(Object $object, $objectId);

    /**
     * @param Object $object
     * @return void
     * @throws Exception
     */
    public function save(Object $object);
}