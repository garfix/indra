<?php

namespace indra\storage;

use indra\diff\DiffItem;

/**
 * @author Patrick van Bergen
 */
class DomainObjectTypeCommit
{
    private $branchId;

    private $typeId;

    private $commitIndex;

    private $diffItems = [];

    public function __construct($branchId, $typeId, $commitIndex)
    {
        $this->branchId = $branchId;
        $this->typeId = $typeId;
        $this->commitIndex = $commitIndex;
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

    public function addDiffItem(DiffItem $diffItem)
    {
        $this->diffItems[] = $diffItem;
    }

    public function getDiffItems()
    {
        return $this->diffItems;
    }
}