<?php

namespace indra\definition;

use indra\object\Attribute;

/**
 * A tool for creating types in the setup tier.
 *
 * @author Patrick van Bergen
 */
class TypeDefinition
{
    protected $attributes = [];

    /**
     * @param Attribute $attribute
     * @return void
     */
    public function addAttribute(Attribute $attribute)
    {
        $this->attributes[$attribute->getId()] = $attribute;
    }

    /**
     * @return Attribute[]
     */
    public function getAttributes()
    {
        return $this->attributes;
    }
}