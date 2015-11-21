<?php

namespace indra\object;

use indra\service\Context;

/**
 * A tool for creating types in the setup tier.
 *
 * @author Patrick van Bergen
 */
class TypeDefinition
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
}