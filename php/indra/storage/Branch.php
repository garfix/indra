<?php

namespace indra\storage;

/**
 * @author Patrick van Bergen
 */
class Branch
{
    const MASTER = 'master----------------';

    protected $branchId = null;

    protected $branchName = null;

    protected $headCommitId = null;

    public function __construct($branchId, $branchName)
    {
        $this->branchId = $branchId;
        $this->branchName = $branchName;
    }

    public function getBranchId()
    {
        return $this->branchId;
    }

    public function getHeadCommitId()
    {
        return $this->headCommitId;
    }

    public function setHeadCommitId($headCommitId)
    {
        $this->headCommitId = $headCommitId;
    }

    public function getBranchName()
    {
        return $this->branchName;
    }

    public function isMaster()
    {
        return $this->branchId == self::MASTER;
    }
}