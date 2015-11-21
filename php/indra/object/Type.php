<?php

namespace indra\object;

use indra\service\Context;

/**
 * @author Patrick van Bergen
 */
class Type
{
    protected $attributes = [];

    public function addAttribute($name)
    {
        $attribute = new Attribute(Context::getIdGenerator()->generateId());
        $attribute->setName($name);

        $this->attributes[$name] = $attribute;

        return $attribute;
    }

    /**
     * @return Attribute[]
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @return Attribute
     */
    public function getAttributeById($id)
    {
        return $this->attributes[$id];
    }
}