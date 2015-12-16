<?php

namespace indra\object;

use indra\storage\TableView;

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
     * @return Attribute|false
     */
    public function getAttributeById($id)
    {
        return isset($this->attributes[$id]) ? $this->attributes[$id] : false;
    }

    /**
     * @return TableView
     */
    public abstract function getTableView();
}