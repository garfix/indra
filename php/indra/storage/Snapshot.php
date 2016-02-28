<?php

namespace indra\storage;

/**
 * @author Patrick van Bergen
 */
class Snapshot implements TableView
{
    /** @var  int */
    private $commitId;

    /** @var  string */
    private $typeId;

    /** @var  string */
    private $viewId;

    public function __construct($commitId, $typeId, $viewId)
    {
        $this->commitId = $commitId;
        $this->typeId = $typeId;
        $this->viewId = $viewId;
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
    public function getTypeId()
    {
        return $this->typeId;
    }

    /**
     * @return string
     */
    public function getViewId()
    {
        return $this->viewId;
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return 'snapshot_' . $this->viewId;
    }
}