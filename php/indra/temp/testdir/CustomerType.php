<?php

namespace indra\temp\testdir;

use indra\object\Attribute;
use indra\object\Type;

/**
 * This class was auto-generated. Do not change it, for it will be overwritten.
 */
class CustomerType extends Type
{
    public function __construct()
    {
        $id = 'jRt9Ja29XsNA0Sd7l6MO';
        $attribute = new Attribute($id);
        $attribute->setName('name');
        $this->attributes[$id] = $attribute;

    }

    /**
     * @return Attribute
     */
    public function getAttributeById($id)
    {
        return $this->attributes[$id];
    }
}
