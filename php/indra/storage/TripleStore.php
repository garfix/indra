<?php

namespace indra\storage;

use Exception;
use indra\object\Instance;

/**
 * @author Patrick van Bergen
 */
interface TripleStore
{
    /**
     * @return void
     */
    public function createBasicTables();

    public function load(Instance $instance, $indraId);

    /**
     * @param Instance $instance
     * @return void
     * @throws Exception
     */
    public function save(Instance $instance);
}