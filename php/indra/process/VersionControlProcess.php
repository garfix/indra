<?php

namespace indra\process;

use indra\service\Context;
use indra\storage\Branch;
use indra\storage\Commit;

/**
 * @author Patrick van Bergen
 */
abstract class VersionControlProcess
{
    /**
     * @param Branch $branch
     * @param $commitDescription
     * @return Commit
     */
    protected function createCommit(Branch $branch, $commitDescription)
    {
        $persistenceStore = Context::getPersistenceStore();

        // update the branch
        $branch->increaseCommitIndex();
        $persistenceStore->storeBranch($branch);

        $dateTime = Context::getDateTimeGenerator()->getDateTime();
        $userName = Context::getUserNameProvider()->getUserName();

        // create a new commit
        $commit = new Commit($branch->getBranchId(), $branch->getCommitIndex(), $commitDescription, $userName, $dateTime->format('Y-m-d H:i:s'));

        // store the commit
        $persistenceStore->storeCommit($commit);

        return $commit;
    }
}