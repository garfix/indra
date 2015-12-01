<?php

namespace indra\object;

use indra\service\Domain;

/**
 * @author Patrick van Bergen
 */
class Model
{
    /** @var  Domain */
    protected $domain;

    public function __construct(Domain $domain)
    {
        $this->domain = $domain;
    }
}