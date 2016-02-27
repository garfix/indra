<?php

namespace indra\object;

use indra\service\Domain;

/**
 * @author Patrick van Bergen
 */
abstract class Model
{
    /** @var  Domain */
    protected $domain;

    public function __construct(Domain $domain)
    {
        $this->domain = $domain;
    }

    /**
     * @return Type
     */
    protected abstract function getType();
}