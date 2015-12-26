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
     * @param string $name
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

    /**
     * @return $this
     */
    public function setDataTypeDate()
    {
        $this->properties['dataType'] = self::TYPE_DATE;
        return $this;
    }

    /**
     * @return $this
     */
    public function setDataTypeTime()
    {
        $this->properties['dataType'] = self::TYPE_TIME;
        return $this;
    }

    /**
     * @return $this
     */
    public function setDataTypeDateTime()
    {
        $this->properties['dataType'] = self::TYPE_DATETIME;
        return $this;
    }

    /**
     * @return $this
     */
    public function setDataTypeInteger()
    {
        $this->properties['dataType'] = self::TYPE_INT;
        return $this;
    }

    /**
     * @return $this
     */
    public function setDataTypeLongText()
    {
        $this->properties['dataType'] = self::TYPE_LONGTEXT;
        return $this;
    }

    /**
     * @return $this
     */
    public function setDataTypeDouble()
    {
        $this->properties['dataType'] = self::TYPE_DOUBLE;
        return $this;
    }

    public function getDataType()
    {
        return $this->properties['dataType'];
    }
}