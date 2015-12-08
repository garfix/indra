<?php

namespace indra\object;

/**
 * Base class for all generated object classes.
 *
 * @author Patrick van Bergen
 */
class Object
{
    /** @var  Type */
    protected $type;

    /** @var  string */
    protected $id;

    /** @var mixed[] */
    protected $attributes = [];

public $loadedAttributeValues = [];

    public function __construct(Type $type, $id)
    {
        $this->type = $type;
        $this->id = $id;
    }

//    /**
//     * @param string $id
//     */
//    public function setId($id)
//    {
//        $this->id = $id;
//    }

    /**
     * @return string An indra identifier.
     */
    public function getId()
    {
        return $this->id;
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
$this->loadedAttributeValues = $attributeValues;
        $this->attributes = $attributeValues;
    }
}