<?php

namespace indra\object;

/**
 * @author Patrick van Bergen
 */
class Attribute
{
    const TYPE_INT = 'int';
    const TYPE_VARCHAR = 'varchar';
    const TYPE_DATE = 'date';
    const TYPE_TIME = 'time';
    const TYPE_DATETIME = 'datetime';
    const TYPE_LONGTEXT = 'longtext';
    const TYPE_DOUBLE = 'double';

    protected $id;

    protected $properties = [
        'name' => null,
        'dataType' => self::TYPE_VARCHAR,
    ];

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return $this
     */
    public function setName($name)
    {
        $this->properties['name'] = $name;
        return $this;
    }

    public function getName()
    {
        return $this->properties['name'];
    }

    /**
     * @return $this
     */
    public function setDataTypeVarchar()
    {
        $this->properties['dataType'] = self::TYPE_VARCHAR;
        return $this;
    }

    public function getDataType()
    {
        return $this->properties['dataType'];
    }
}