<?php

namespace indra\definition;

use indra\object\Attribute;
use indra\service\Context;

/**
 * @author Patrick van Bergen
 */
class AttributeDefinition
{
    /**
     * @param string $name
     * @return Attribute
     */
    public static function create($name)
    {
        $attribute = new Attribute(Context::getIdGenerator()->generateId(), $name);
        return $attribute;
    }
}