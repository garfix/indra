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

    public function load(Object $instance, $indraId);

    /**
     * @param Object $instance
     * @return void
     * @throws Exception
     */
    public function save(Object $instance);
}