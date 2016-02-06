<?php

namespace indra\storage;

/**
 * @author Patrick van Bergen
 */
class Branch
{
    const MASTER = 'master----------------';

    /** @var  Revision */
    protected $activeRevision = null;

    protected $branchId = null;

    protected $commitIndex = null;

    /** @var Commit */
    protected $motherCommitIndex = null;

    public function __construct($branchId, $motherBranchId = null, $motherCommitIndex = null)
    {
        $this->branchId = $branchId;
        $this->motherBranchId = $motherBranchId;
        $this->motherCommitIndex = $motherCommitIndex;
    }

    public function getBranchId()
    {
        return $this->branchId;
    }

    public function getMotherBranchId()
    {
        return $this->motherBranchId;
    }

    public function getMotherCommitIndex()
    {
        return $this->motherCommitIndex;
    }

    public function setCommitIndex($commitIndex)
    {
        $this->commitIndex = $commitIndex;
    }

    public function getCommitIndex()
    {
        return $this->commitIndex;
    }

    public function increaseCommitIndex()
    {
        $this->commitIndex++;
    }

    public function isMaster()
    {
        return $this->branchId == self::MASTER;
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