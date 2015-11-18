<?php

namespace indra\object;

use indra\service\IdGenerator;

/**
 * @author Patrick van Bergen
 */
class Type
{
    protected $attributes = [];

    public function addAttribute($name)
    {
        $attribute = new Attribute(IdGenerator::generateId());
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
}