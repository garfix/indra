<?php

namespace indra\storage;

use indra\service\Context;

/**
 * @author Patrick van Bergen
 */
class Branch
{
    const MASTER = 'master----------------';

    /** @var  string */
    protected $id;

    /** @var  Revision */
    protected $activeRevision = null;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function isMaster()
    {
        return $this->id == self::MASTER;
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