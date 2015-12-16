<?php

namespace indra\storage;

use indra\service\Context;
use indra\object\DomainObject;

/**
 * @author Patrick van Bergen
 */
class Revision
{
    /** @var  string */
    private $id;

    /** @var  string */
    private $description;

    /** @var  Revision */
    private $sourceRevision;

    public function __construct($id)
    {
//        $this->description = $description;
        $this->id = $id;
    }

    /**
     * @return string Indra id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param Revision $revision
     */
    public function setSourceRevision(Revision $revision)
    {
        $this->sourceRevision = $revision;
    }

    /**
     * @return Revision
     */
    public function getSourceRevision()
    {
        return $this->sourceRevision;
    }

    /**
     * @param $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
}