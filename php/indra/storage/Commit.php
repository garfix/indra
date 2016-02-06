<?php

namespace indra\storage;

/**
 * @author Patrick van Bergen
 */
class Commit
{
    private $branchId;

    private $commitIndex;

    private $reason;

    private $userName;

    private $dateTime;

    private $mergeBranchId;

    private $mergeCommitIndex;

    public function __construct($branchId, $commitIndex, $reason, $userName, $dateTime, $mergeBranchId = null, $mergeBranchIndex = null)
    {
        $this->branchId = $branchId;
        $this->commitIndex = $commitIndex;
        $this->reason = $reason;
        $this->userName = $userName;
        $this->dateTime = $dateTime;
        $this->mergeBranchId = $mergeBranchId;
        $this->mergeCommitIndex = $mergeBranchIndex;
    }

    /**
     * @return mixed
     */
    public function getBranchId()
    {
        return $this->branchId;
    }

    /**
     * @return mixed
     */
    public function getCommitIndex()
    {
        return $this->commitIndex;
    }

    /**
     * @return mixed
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * @return mixed
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * @return mixed
     */
    public function getDateTime()
    {
        return $this->dateTime;
    }

    /**
     * @return null
     */
    public function getMergeBranchId()
    {
        return $this->mergeBranchId;
    }

    /**
     * @return null
     */
    public function getMergeCommitIndex()
    {
        return $this->mergeCommitIndex;
    }
}
