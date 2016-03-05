<?php

namespace indra\process;

use indra\exception\DiffItemClassNotRecognizedException;
use indra\object\Type;
use indra\service\Context;
use indra\storage\Branch;
use indra\storage\Commit;
use indra\storage\DiffService;
use indra\storage\Snapshot;


/**
 * @author Patrick van Bergen
 */
class CreateSnapshot extends VersionControlProcess
{
    /**
     * @param Branch $branch
     * @param Commit $commit
     * @param Type $type
     * @return Snapshot
     * @throws DiffItemClassNotRecognizedException
     */
    public function run(Branch $branch, Commit $commit, Type $type)
    {
        $diffService = new DiffService();
        $persistenceStore = Context::getPersistenceStore();

        $snapshot = new Snapshot($commit->getCommitId(), $type->getId(), Context::getIdGenerator()->generateId());
        $persistenceStore->storeSnapshot($snapshot, $persistenceStore->loadBranchView($branch->getBranchId(), $type->getId()));

        $commitId = $branch->getHeadCommitId();

        while ($commitId != null && $commitId != $commit->getCommitId()) {

            $inBetweenCommit = Context::getPersistenceStore()->loadCommit($commitId);

            foreach (Context::getPersistenceStore()->loadDomainObjectTypeCommitsForType($inBetweenCommit, $type) as $domainObjectTypeCommit) {

                $reversedDiffItems = [];

                foreach (array_reverse($domainObjectTypeCommit->getDiffItems()) as $diffItem) {
                    $reversedDiffItems[] = $diffService->getReverseDiffItem($diffItem);
                }

                foreach ($reversedDiffItems as $diffItem) {
                    $persistenceStore->processDiffItem($snapshot, $diffItem);
                }
            }

            $commitId = $inBetweenCommit->getMotherCommitId();
        }

        return $snapshot;
    }
}