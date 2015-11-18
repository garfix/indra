<?php

namespace indra\temp\testdir;

use indra\object\Instance;

/**
 * This class was auto-generated. Do not change it, for it will be overwritten.
 */
class Customer extends Instance
{
    protected $id;

    /**
     * @return string An indra identifier.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return $this
     */
    public function setName($attribute)
    {
        $this->attributes['name'] = $attribute;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return isset($this->attributes['name']) ? $this->attributes['name'] : null;
    }

}
