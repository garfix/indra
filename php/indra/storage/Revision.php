<?php

namespace indra\storage;

use indra\service\Context;
use indra\object\Object;

/**
 * @author Patrick van Bergen
 */
class Revision
{
    /** @var  string */
    private $description;

    /** @var  string */
    private $id;

    /** @var Object[] */
    public $saveList;

    public function __construct($description)
    {
        $this->description = $description;
        $this->id = Context::getIdGenerator()->generateId();
    }

    /**
     * @return string Indra id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param Object $object
     */
    public function addToSaveList(Object $object)
    {
        $this->saveList[] = $object;
    }

    /**
     * @return Object[]
     */
    public function getSaveList()
    {
        return $this->saveList;
    }
}