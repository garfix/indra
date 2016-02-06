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
    public $originalAttributes = [];

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

    public function setAttributeValues($attributeValues)
    {
#todo check!!!
        $this->attributes = $attributeValues;
    }

    public function getChangedAttributeValues()
    {
        $attributes = [];

        foreach ($this->attributes as $attributeTypeId => $newValue) {

            $oldValue = (isset($this->originalAttributes[$attributeTypeId]) ? $this->originalAttributes[$attributeTypeId] : null);

            if ($newValue !== $newValue) {

                $attributes[] = [$oldValue, $newValue];

            }
        }

        return $attributes;
    }
}