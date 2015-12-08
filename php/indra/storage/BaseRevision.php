<?php

namespace indra\storage;

/**
 * @author Patrick van Bergen
 */
class BaseRevision extends Revision
{
    const ID = 'base------------------';

    public function __construct()
    {
        parent::__construct(self::ID);
    }
}