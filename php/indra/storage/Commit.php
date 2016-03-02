<?php

namespace indra\storage;

/**
 * @author Patrick van Bergen
 */
class Commit
{
    private $commitId;

    private $motherCommitId;

    private $fatherCommitId;

    private $reason;

    private $userName;

    private $dateTime;

    public function __construct($commitId, $motherCommitId, $reason, $userName, $dateTime, $fatherCommitId = null)
    {
        $this->commitId = $commitId;
        $this->motherCommitId = $motherCommitId;
        $this->fatherCommitId = $fatherCommitId;
        $this->reason = $reason;
        $this->userName = $userName;
        $this->dateTime = $dateTime;
    }

    /**
     * @return string
     */
    public function getCommitId()
    {
        return $this->commitId;
    }

    /**
     * @return string
     */
    public function getMotherCommitId()
    {
        return $this->motherCommitId;
    }

    /**
     * @return string
     */
    public function getFatherCommitId()
    {
        return $this->fatherCommitId;
    }

    /**
     * Only used by Rebase
     *
     * @return string
     */
    public function setMotherCommitId($commitId)
    {
        $this->motherCommitId = $commitId;
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
}
