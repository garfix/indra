<?php

namespace indra\storage;

use Exception;
use indra\object\Object;

/**
 * @author Patrick van Bergen
 */
interface TripleStore
{
    /**
     * @return void
     */
    public function createBasicTables();

    public function load(Object $object, $indraId);

    /**
     * @param Object $object
     * @return void
     * @throws Exception
     */
    public function save(Object $object);
}