<?php

namespace indra\definition;

use indra\object\Attribute;
use indra\service\Context;

/**
 * A tool for creating types in the setup tier.
 *
 * @author Patrick van Bergen
 */
class TypeDefinition
{
    protected $attributes = [];

    /**
     * @param string $name
     * @return Attribute
     */
    public function addAttribute($name)
    {
        $attribute = new Attribute(Context::getIdGenerator()->generateId(), $name);
        $this->attributes[] = $attribute;
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