<?php

namespace indra\storage;

/**
 * @author Patrick van Bergen
 */
class Branch
{
    const MASTER = 'master----------------';

    protected $branchId = null;

    protected $commitId = null;

    public function __construct($branchId)
    {
        $this->branchId = $branchId;
    }

    public function getBranchId()
    {
        return $this->branchId;
    }

    public function getCommitId()
    {
        return $this->commitId;
    }

    public function setCommitId($commitId)
    {
        $this->commitId = $commitId;
    }

    public function isMaster()
    {
        return $this->branchId == self::MASTER;
    }
}