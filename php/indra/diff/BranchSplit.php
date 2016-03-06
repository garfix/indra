<?php

namespace indra\diff;

/**
 * This is the revert of a merge.
 *
 * @author Patrick van Bergen
 */
class BranchSplit extends DiffItem
{
    private $commitIds = null;

    public function __construct($commitIds)
    {
        $this->commitIds = $commitIds;
    }

    public function getCommitIds()
    {
        return $this->commitIds;
    }
}