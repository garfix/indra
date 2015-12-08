<?php

namespace indra\service;

use indra\storage\BaseRevision;
use indra\storage\Branch;
use indra\storage\MasterBranch;
use indra\storage\Revision;

/**
 * @author Patrick van Bergen
 */
class BranchModel
{
    /** @var  Branch */
    private $activeBranch;

//    /**
//     * @return Branch
//     */
//    public function startNewBranch()
//    {
//        $this->activeBranch = new Branch();
//
//        return $this->activeBranch;
//    }
//
//    /**
//     * @param Branch $branch
//     */
//    public function startBranch(Branch $branch)
//    {
//        $this->activeBranch = $branch;
//    }
//
//    /**
//     * @return MasterBranch
//     */
//    public function getActiveBranch()
//    {
//        return $this->activeBranch ?: $this->activeBranch = new MasterBranch();
//    }
//
//    /**
//     * @param Branch $source
//     */
//    public function mergeBranch(Branch $source, Branch $target)
//    {
//        $tripleStore = Context::getTripleStore();
//
//        // find all revisions of $branch after the common revision
//        $revisionIds = $this->findMergeableRevisions($target, $source);
//
//        // apply these revisions to the other branch
//        $mergeRevision = new Revision(Context::getIdGenerator()->generateId());
//        $mergeRevision->setSourceRevision($target->getActiveRevision());
//        $target->setActiveRevision($mergeRevision);
//        $tripleStore->mergeRevisions($source, $target, $mergeRevision, $revisionIds);
//        $tripleStore->saveBranch($target);
//    }
//
//    private function findMergeableRevisions(Branch $branch1, Branch $branch2)
//    {
//        $tripleStore = Context::getTripleStore();
//
//        $branch1Revisions = [];
//        $branch2Revisions = [];
//
//        $branch1RevisionId = $branch1->getActiveRevision()->getId();
//        $branch2RevisionId = $branch2->getActiveRevision()->getId();
//
//        do {
//
//            $branch1Revisions[] = $branch1RevisionId;
//            $branch2Revisions[] = $branch2RevisionId;
//
//            $branch1Source = $tripleStore->getSourceRevisionId($branch1RevisionId);
//            $branch2Source = $tripleStore->getSourceRevisionId($branch2RevisionId);
//
////var_dump($branch1Source);
////var_dump($branch2Source);
//
//            // common revision found!
//            if (in_array($branch2Source, $branch1Revisions)) {
//                break;
//            }
//            if (in_array($branch1Source, $branch2Revisions)) {
//                break;
//            }
//
//            $branch1RevisionId = $branch1Source;
//            $branch2RevisionId = $branch2Source;
//
//        } while ($branch1RevisionId != BaseRevision::ID);
//
//        return array_reverse($branch2Revisions);
//    }
}