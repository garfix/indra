<?php

namespace indra\diff;


/**
 * @author Patrick van Bergen
 */
class ObjectRemoved extends DiffItem
{
    private $objectId;

    /** @var  array An array of attribute-id => [old value, value] */
    private $attributeValues;

    public function __construct($objectId, $attributeValues)
    {
        $this->objectId = $objectId;
        $this->attributeValues = $attributeValues;
    }

    /**
     * @return mixed
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * @return mixed
     */
    public function getAttributeValues()
    {
        return $this->attributeValues;
    }
}