<?php

namespace indra\object;

/**
 * Base class for all generated object classes.
 *
 * @author Patrick van Bergen
 */
class Instance
{
    /** @var  Type */
    protected $type;

    /** @var  string */
    protected $id;

    /** @var mixed[] */
    protected $attributes = [];

    public function __construct(Type $type)
    {
        $this->type = $type;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return Type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed[]
     */
    public function getAttributeValues()
    {
        return $this->attributes;
    }

    public function setAttributeValues($attributeValues)
    {
#todo check!!!
        $this->attributes = $attributeValues;
    }
}