<?php

namespace indra\diff;

/**
 * Represents the changes that occurred when a branch was merged.
 * Since this diff applies only to a single type, only the commits that involve a change in the type are stored.
 *
 * @author Patrick van Bergen
 */
class BranchMerged extends DiffItem
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