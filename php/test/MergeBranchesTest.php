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
#todo remove revision
        $commit = $domain->commit('Add customer Dr. Jones');

        // start new branch and change customer
#var_dump("XXX start new branch and change customer");

#$customer = $customerModel->loadCustomer($customerId);
        $branch = $domain->startNewBranch($commit);
        #todo: set current rev to basic rev

        $customer = $customerModel->loadCustomer($customerId);
        $customer->setName('Dr. Who');
        $customerModel->saveCustomer($customer);
        $domain->commit('Change customer name to Dr. Who');

        $master = $domain->getMasterBranch();

#var_dump("XXX change in master branch");

        // change in master branch
        $domain->startBranch($master);
        $customer = $customerModel->loadCustomer($customerId);
        $customer->setBirthDate('1971-09-23');
        $customerModel->saveCustomer($customer);
        $domain->commit('Change birth date to 1971-09-23');

#var_dump("XXX test");

        // test that the two objects have separated
        $customer2 = $customerModel->loadCustomer($customerId);
        $this->assertEquals('Dr. Jones', $customer2->getName());
        $this->assertEquals('1971-09-23', $customer2->getBirthDate());

        $domain->startBranch($branch);
        $customer3 = $customerModel->loadCustomer($customerId);
        $this->assertEquals('Dr. Who', $customer3->getName());
        $this->assertEquals('1969-11-24', $customer3->getBirthDate());

        // merge new branch to master
        $domain->mergeBranch($branch, $master, "Merge");

# bij het wegschrijven naar de nieuwe branch worden ook de niet veranderde attributen opnieuw weggeschreven
# maar dat is hier niet alleen het probleem

        // test that the merge succeeded and that only the change was applied
        $domain->startBranch($master);
        $customer4 = $customerModel->loadCustomer($customerId);
        $this->assertEquals('Dr. Who', $customer4->getName());
        $this->assertEquals('1971-09-23', $customer4->getBirthDate());
    }
}