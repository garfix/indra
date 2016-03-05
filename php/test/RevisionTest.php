<?php

use indra\service\Context;
use indra\service\Domain;
use my_module\customer\CustomerModel;

require_once __DIR__ . '/Base.php';

/**
 * @author Patrick van Bergen
 */
class RevisionTest extends Base
{
    public static function setUpBeforeClass()
    {
        parent::initialize();
        parent::createCustomerType();
    }

    public function testUndo()
    {
        $domain = new Domain();
        $customerModel = new CustomerModel($domain);

        $customer = $customerModel->createCustomer();
        $id = $customer->getId();

        // initial name
        $customer->setName('Dr. Jones');
        $customerModel->saveCustomer($customer);
        $domain->commit('Add customer Dr. Jones');

        // change name
        $customer->setName('Dr. Who');
        $customerModel->saveCustomer($customer);
        $commit = $domain->commit('Dr. Jones renamed to Dr. Who');

        $commitId = $commit->getCommitId();
        $commit = $domain->getCommitById($commitId);

        $undoCommit = $domain->revertCommit($commit);

        // test revert
        $customer2 = $customerModel->loadCustomer($id);
        $name = $customer2->getName();
        $this->assertEquals('Dr. Jones', $name);

        $domain->revertCommit($undoCommit);

        // test revert revert
        $customer3 = $customerModel->loadCustomer($id);
        $name = $customer3->getName();
        $this->assertEquals('Dr. Who', $name);
    }

    public function testGetCommitList()
    {
        $domain = new Domain();
        $customerModel = new CustomerModel($domain);

        $customer = $customerModel->createCustomer();
        $customer->setName('Dr. Jones');
        $customerModel->saveCustomer($customer);
        $domain->commit('Add customer Dr. Jones');

        $customer->setName('Dr. Who');
        $customerModel->saveCustomer($customer);
        $domain->commit('Dr. Jones renamed to Dr. Who');

        $commits = $domain->getCommitList($domain->getMasterBranch()->getHeadCommitId());
        $this->assertEquals(2, count($commits));
        $headCommit = $commits[0];
        $this->assertEquals("Dr. Jones renamed to Dr. Who", $headCommit->getReason());
    }

    public function testRemoveBranch()
    {
        $domain = new Domain();
        $customerModel = new CustomerModel($domain);

        $customer = $customerModel->createCustomer();
        $customer->setName('Dr. Jones');
        $customerModel->saveCustomer($customer);
        $baseCommit = $domain->commit('Add customer Dr. Jones');

        // branch 1 from master

        $branch1 = $domain->checkoutNewBranch("Branch 1");

        $customer->setName('Dr. Who');
        $customerModel->saveCustomer($customer);
        $commit1 = $domain->commit('Dr. Jones renamed to Dr. Who');

        // branch 2 from master

        $domain->checkoutBranch($domain->getMasterBranch());
        $branch2 = $domain->checkoutNewBranch("Branch 2");

        $customer->setName('Dr. Frankenstein');
        $customerModel->saveCustomer($customer);
        $commit2 = $domain->commit('Dr. Jones renamed to Dr. Frankenstein');

        $domain->checkoutBranch($domain->getMasterBranch());
        $domain->mergeBranch($branch2, "Merge Branch 2 into Master");

        // remove both branches
        $domain->removeBranch($branch1);
        $domain->removeBranch($branch2);

        // commit 1 should be removed
        $this->assertNull($domain->getCommitById($commit1->getCommitId()));

        // commit 2 should still exists
        $this->assertNotNull($domain->getCommitById($commit2->getCommitId()));

        // base commit should still exist
        $this->assertNotNull($domain->getCommitById($baseCommit->getCommitId()));

        // both branch views should have been deleted
        $this->assertNull(Context::getPersistenceStore()->loadBranchView($branch1->getBranchId(), $customer->getType()->getId()));
        $this->assertNull(Context::getPersistenceStore()->loadBranchView($branch2->getBranchId(), $customer->getType()->getId()));
    }

    public function testUndoMerge()
    {
        $domain = new Domain();
        $customerModel = new CustomerModel($domain);

        $customer = $customerModel->createCustomer();
        $customer->setName('Dr. Atkinson');
        $customerModel->saveCustomer($customer);
        $domain->commit('Add customer Dr. Atkinson');

        // branch 1 from master

        $branch1 = $domain->checkoutNewBranch("Branch 1");

        $customer->setName('Drs. P');
        $customerModel->saveCustomer($customer);
        $domain->commit('Dr. Jones renamed to Drs. P');

        $customer->setName('Mrs. Jones');
        $customerModel->saveCustomer($customer);
        $domain->commit('Drs. P renamed to Mrs. Jones');

        // merge

        $domain->checkoutBranch($domain->getMasterBranch());
        $mergeCommit = $domain->mergeBranch($branch1, "Merge Branch 1 into Master");

        // pre-check

        $domain->checkoutBranch($domain->getMasterBranch());
        $customer1 = $customerModel->loadCustomer($customer->getId());
        $this->assertSame("Mrs. Jones", $customer1->getName());

        // revert merge

        $domain->revertCommit($mergeCommit);

        // after-check

        $customer2 = $customerModel->loadCustomer($customer->getId());
        $this->assertSame("Dr. Atkinson", $customer2->getName());
    }
}
