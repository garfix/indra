<?php

namespace indra\storage;

use indra\service\Context;

/**
 * @author Patrick van Bergen
 */
class Branch
{
    /** @var  string */
    private $id;

    public function __construct()
    {
        $this->id = Context::getIdGenerator()->generateId();
    }

}