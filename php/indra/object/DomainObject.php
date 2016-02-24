<?php

namespace indra\object;

/**
 * Base class for all generated object classes.
 *
 * @author Patrick van Bergen
 */
class DomainObject
{
    /** @var  Type */
    protected $type;

    /** @var  string */
    protected $id;

    /** @var mixed[] */
    protected $attributes = [];

    /** @var mixed[]  */
    protected $originalAttributes = [];

    public function __construct(Type $type, $id, $attributes)
    {
        $this->type = $type;
        $this->id = $id;
        $this->attributes = $attributes;
        $this->originalAttributes = $attributes;
    }

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

    /**
     * @return array
     */
    public function getOriginalAttributeValues()
    {
        return $this->originalAttributes;
    }

    public function setAttributeValues($attributeValues)
    {
#todo check!!!
        $this->attributes = $attributeValues;
    }

    public function isNew()
    {
        return empty($this->originalAttributes);
    }

    public function markAsSaved()
    {
        $this->originalAttributes = $this->attributes;
    }

    public function getChangedAttributeValues()
    {
        $attributes = [];

        foreach ($this->attributes as $attributeTypeId => $newValue) {

            $oldValue = (isset($this->originalAttributes[$attributeTypeId]) ? $this->originalAttributes[$attributeTypeId] : null);

            if ($oldValue !== $newValue) {

                $attributes[$attributeTypeId] = [$oldValue, $newValue];

            }
        }

        return $attributes;
    }
}