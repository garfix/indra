<?php

namespace indra\object;

/**
 * @author Patrick van Bergen
 */
abstract class Type
{
    protected $attributes = [];

    /**
     * @return string Indra id.
     */
    public abstract function getId();

    /**
     * @return Attribute[]
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @param string $id
     * @return false|Attribute
     */
    public function getAttributeById($id)
    {
        return isset($this->attributes[$id]) ? $this->attributes[$id] : false;
    }
}