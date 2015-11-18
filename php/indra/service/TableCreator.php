<?php

namespace indra\service;

/**
 * @author Patrick van Bergen
 */
class TableCreator
{
    /**
     * Creates all basic Indra tables.
     */
    public function createBasicTables()
    {
        Context::getTripleStore()->createBasicTables();
    }
}