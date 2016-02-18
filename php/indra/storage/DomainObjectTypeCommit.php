<?php

namespace indra\storage;

/**
 * @author Patrick van Bergen
 */
class DomainObjectTypeCommit
{
    private $branchId;

    private $typeId;

    private $commitIndex;

    private $diffItems = [];

    public function __construct($branchId, $typeId, $commitIndex, array $diffItems)
    {
        $this->branchId = $branchId;
        $this->typeId = $typeId;
        $this->commitIndex = $commitIndex;
        $this->diffItems = $diffItems;
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
     * @return int
     */
    public function getCommitIndex()
    {
        return $this->commitIndex;
    }

    public function getDiffItems()
    {
        return $this->diffItems;
    }
}