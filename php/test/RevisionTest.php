<?php

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
}
