<?php

use indra\service\BranchModel;
use indra\service\Context;
use indra\service\Domain;
use my_module\customer\CustomerModel;

require_once __DIR__ . '/TestBase.php';

/**
 * @author Patrick van Bergen
 */
class MergeBranchesTest extends TestBase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        parent::createCustomerType();
    }

    public function testMerge()
    {
# andere naam, split up context ($work?)
        $domain = Domain::loadFromIni();
        $customerModel = new CustomerModel($domain);

        $domain->getRevisionModel()->createRevision('Add customer Dr. Jones');
        $customer = $customerModel->createCustomer();
        $customerId = $customer->getId();
        $customer->setName('Dr. Jones');
        $customer->setBirthDate('1969-11-24');
        $customerModel->saveCustomer($customer);

        // start new branch and change customer
        $domain->getBranchModel()->startNewBranch();
        $domain->getRevisionModel()->createRevision('Change customer name to Dr. Who');
        $customer->setName('Dr. Who');
        $customerModel->saveCustomer($customer);
        $branch = $domain->getBranchModel()->getActiveBranch();

        // change in master branch
        $domain->getBranchModel()->startBranch(BranchModel::MASTER);
        $domain->getRevisionModel()->startRevision('Add customer Dr. Jones');
        $customer->setBirthDate('1971-09-23');
        $customerModel->saveCustomer($customer);
        $master = $branchId = $domain->getBranchModel()->getActiveBranch();

        // merge new branch to master
        $domain->getBranchModel()->mergeBranch($branch);

        // test that the merge succeeded and that only the change was applied
        $customer2 = $customerModel->loadCustomer($customerId);
        $this->assertEquals('1971-09-23', $customer->getBirthDate());
        $this->assertEquals('Dr. Who', $customer->getBirthDate());
    }

# context en context zijn twee verschillende dingen
}