<?php

namespace indra\service;

use indra\storage\Branch;

/**
 * @author Patrick van Bergen
 */
class BranchModel
{
    public function startNewBranch()
    {
        return new Branch();
    }
}