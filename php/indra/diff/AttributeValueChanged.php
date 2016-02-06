<?php

namespace indra\diff;

/**
 * @author Patrick van Bergen
 */
class AttributeValueChanged extends DiffItem
{
    private $objectId;

    private $attributeTypeId;

    private $oldValue;

    private $newValue;

    public function __construct($objectId, $attributeTypeId, $oldValue, $newValue)
    {
        $this->objectId = $objectId;
        $this->attributeTypeId = $attributeTypeId;
        $this->oldValue = $oldValue;
        $this->newValue = $newValue;
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
    public function getAttributeTypeId()
    {
        return $this->attributeTypeId;
    }

    /**
     * @return mixed
     */
    public function getOldValue()
    {
        return $this->oldValue;
    }

    /**
     * @return mixed
     */
    public function getNewValue()
    {
        return $this->newValue;
    }


}