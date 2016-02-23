<?php

namespace indra\storage;

/**
 * @author Patrick van Bergen
 */
class Snapshot implements TableView
{
    /** @var  string */
    private $branchId;

    /** @var  int */
    private $commitIndex;

    /** @var  string */
    private $typeId;

    /** @var  string */
    private $viewId;

    public function __construct($branchId, $commitIndex, $typeId, $viewId)
    {
        $this->branchId = $branchId;
        $this->commitIndex = $commitIndex;
        $this->typeId = $typeId;
        $this->viewId = $viewId;
    }

    /**
     * @return string
     */
    public function getBranchId()
    {
        return $this->branchId;
    }

    /**
     * @return int
     */
    public function getCommitIndex()
    {
        return $this->commitIndex;
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