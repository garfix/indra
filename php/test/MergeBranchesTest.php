<?php

use indra\service\Domain;
use my_module\customer\CustomerModel;

require_once __DIR__ . '/Base.php';

/**
 * @author Patrick van Bergen
 */
class MergeBranchesTest extends Base
{
    public static function setUpBeforeClass()
    {
        parent::initialize();
        parent::createCustomerType();
    }

    public function testMerge()
    {
        $domain = new Domain();
        $customerModel = new CustomerModel($domain);

        // create customer in default branch (master)

        $customer = $customerModel->createCustomer();
        $customerId = $customer->getId();
        $customer->setName('Dr. Jones');
        $customer->setBirthDate('1969-11-24');
        $customerModel->saveCustomer($customer);
        $commit = $domain->commit('Add customer Dr. Jones');

        // start new branch and change customer

        $branch = $domain->checkoutNewBranch();
        $branchId = $branch->getBranchId();

        $customer = $customerModel->loadCustomer($customerId);
        $customer->setName('Dr. Who');
        $customerModel->saveCustomer($customer);
        $domain->commit('Change customer name to Dr. Who');

        $master = $domain->getMasterBranch();

        // change in master branch
        $domain->checkoutBranch($master);
        $customer = $customerModel->loadCustomer($customerId);
        $customer->setBirthDate('1971-09-23');
        $customerModel->saveCustomer($customer);
        $domain->commit('Change birth date to 1971-09-23');

        // test that the two objects have separated
        $customer2 = $customerModel->loadCustomer($customerId);
        $this->assertEquals('Dr. Jones', $customer2->getName());
        $this->assertEquals('1971-09-23', $customer2->getBirthDate());

        $domain->checkoutBranch($branch);
        $customer3 = $customerModel->loadCustomer($customerId);
        $this->assertEquals('Dr. Who', $customer3->getName());
        $this->assertEquals('1969-11-24', $customer3->getBirthDate());

        // merge new branch to master
        $domain->checkoutBranch($master);
        $branch = $domain->getBranchById($branchId);
        $domain->mergeBranch($branch, "Merge");

        // test that the merge succeeded and that only the change was applied
        $domain->checkoutBranch($master);
        $customer4 = $customerModel->loadCustomer($customerId);
        $this->assertEquals('Dr. Who', $customer4->getName());
        $this->assertEquals('1971-09-23', $customer4->getBirthDate());

        // make a change in new branch

        $domain->checkoutBranch($branch);
        $customer4 = $customerModel->loadCustomer($customerId);
        $customer4->setBirthDate('1938-02-28');
        $customerModel->saveCustomer($customer4);
        $domain->commit('Changed customer\'s birthdate');

        // merge again

//        $domain->checkoutBranch($master);
//        $branch = $domain->getBranchById($branchId);
//        $domain->mergeBranch($branch, "Merge again");
//
//        // only the last commit should be reapplied, not the rest of the branch
//
//        $customer5 = $customerModel->loadCustomer($customerId);
//        $this->assertEquals('Dr. Who', $customer5->getName());
//        $this->assertEquals('1938-02-28', $customer5->getBirthDate());

    }
}