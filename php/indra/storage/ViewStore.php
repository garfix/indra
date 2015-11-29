<?php

namespace indra\storage;

use indra\object\Object;
use indra\object\Type;

/**
 * @author Patrick van Bergen
 */
interface ViewStore
{
    public function createView(Type $type);

    public function updateView(Object $object);
}