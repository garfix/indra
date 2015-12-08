<?php

namespace indra\storage;

/**
 * @author Patrick van Bergen
 */
class MasterBranch extends Branch
{
    const ID = 'master----------------';

    public function __construct()
    {
        $this->id = self::ID;
    }
}