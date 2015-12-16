<?php

namespace indra\storage;

use indra\object\DomainObject;
use indra\object\Type;

/**
 * @author Patrick van Bergen
 */
interface ViewStore
{
    public function createView(Type $type);

    public function updateView(DomainObject $object);
}