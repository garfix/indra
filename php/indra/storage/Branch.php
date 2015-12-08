<?php

namespace indra\storage;

use indra\service\Context;

/**
 * @author Patrick van Bergen
 */
class Branch
{
    /** @var  string */
    protected $id;

    /** @var  Revision */
    protected $activeRevision = null;

    public function __construct()
    {
        $this->id = Context::getIdGenerator()->generateId();
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @param Revision $revision
     */
    public function setActiveRevision(Revision $revision)
    {
        $this->activeRevision = $revision;
    }

    /**
     * @return Revision
     */
    public function getActiveRevision()
    {
        return $this->activeRevision;
    }
}