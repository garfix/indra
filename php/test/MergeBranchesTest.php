<?php

use indra\service\Domain;
use indra\storage\BaseRevision;
use indra\storage\MasterBranch;
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
        $master = new MasterBranch();
        $master->setActiveRevision(new BaseRevision());

        $domain = Domain::loadFromSettings(true);
        $customerModel = new CustomerModel($domain);
#\indra\service\Context::getDB()->setEchoQueries();
        $revision = $domain->createRevision('Add customer Dr. Jones');
        $customer = $customerModel->createCustomer();
        $customerId = $customer->getId();
        $customer->setName('Dr. Jones');
        $customer->setBirthDate('1969-11-24');
        $customerModel->saveCustomer($customer);
        $domain->commitRevision($revision);

        // start new branch and change customer
$customer = $customerModel->loadCustomer($customerId);
        $branch = $domain->startNewBranch();
        $revision = $domain->createRevision('Change customer name to Dr. Who');

#todo: dit moet ook werken
//        $customer = $customerModel->loadCustomer($customerId);
        $customer->setName('Dr. Who');
        $customerModel->saveCustomer($customer);
        $domain->commitRevision($revision);

        // change in master branch
        $domain->startBranch($master);
        $revision = $domain->createRevision('Change birth date to 1971-09-23');
        $customer = $customerModel->loadCustomer($customerId);
        $customer->setBirthDate('1971-09-23');
        $customerModel->saveCustomer($customer);
        $domain->commitRevision($revision);

        // test that the two objects have separated
        $domain->startBranch($master);
        $customer2 = $customerModel->loadCustomer($customerId);
        $this->assertEquals('Dr. Jones', $customer2->getName());
        $this->assertEquals('1971-09-23', $customer2->getBirthDate());

        $domain->startBranch($branch);
        $customer3 = $customerModel->loadCustomer($customerId);
        $this->assertEquals('Dr. Who', $customer3->getName());
        $this->assertEquals('1969-11-24', $customer3->getBirthDate());

        // merge new branch to master
        $domain->mergeBranch($branch, $master);

# bij het wegschrijven naar de nieuwe branch worden ook de niet veranderde attributen opnieuw weggeschreven
# maar dat is hier niet alleen het probleem

        // test that the merge succeeded and that only the change was applied
        $domain->startBranch($master);
        $customer4 = $customerModel->loadCustomer($customerId);
        $this->assertEquals('Dr. Who', $customer4->getName());
        $this->assertEquals('1971-09-23', $customer4->getBirthDate());
    }
}