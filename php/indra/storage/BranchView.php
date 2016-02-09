<?php

namespace indra\storage;


/**
 * @author Patrick van Bergen
 */
class BranchView
{
    /** @var  string */
    private $branchId;

    /** @var  string */
    private $typeId;

    /** @var  string */
    private $viewId;

    public function __construct($branchId, $typeId, $viewId)
    {
        $this->branchId = $branchId;
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
}