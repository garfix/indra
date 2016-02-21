<?php

use indra\service\Domain;
use indra\storage\Commit;
use my_module\customer\Customer;
use my_module\customer\CustomerModel;

require_once __DIR__ . '/Base.php';

/**
 * @author Patrick van Bergen
 */
class CheckoutTest extends Base
{
    public static function setUpBeforeClass()
    {
        parent::initialize();
        parent::createCustomerType();
    }

    public function _testCreateObject()
    {
        $domain = new Domain();
        $model = new CustomerModel($domain);

        /** @var Customer $c1 */
        /** @var Customer $c2 */
        /** @var Commit $specialCommit */
        list($c1, $c2, $specialCommit) = $this->setupFixture($domain, $model);

        // pre-test
        $d1 = $model->loadCustomer($c1->getId());
        $d2 = $model->loadCustomer($c2->getId());
        $this->assertSame('Holy Oak 29', $d1->getAddress());
        $this->assertSame('Church field 1', $d2->getAddress());

        // check out some non-final commit of another branch
        $domain->checkoutCommit($specialCommit);

        // post test
        $e1 = $model->loadCustomer($c1->getId());
        $e2 = $model->loadCustomer($c2->getId());
        $this->assertSame('Springs fall 222', $e1->getAddress());
        $this->assertSame('Barker Gulch', $e2->getAddress());

        // make sure no commits are possible in this state
    }

    private function setupFixture(Domain $domain, CustomerModel $model)
    {
        $c1 = $model->createCustomer();
        $c1->setName('Harry');
        $model->saveCustomer($c1);

        $c2 = $model->createCustomer();
        $c2->setName('Sally');
        $model->saveCustomer($c2);

        $domain->commit("Harry meets Sally");

        $c1->setAddress("Grand View 221b");
        $c2->setAddress("Rodeo Drive 56");

        $domain->commit("Change of addresses  - master 1");

        // do some changes in a new branch

        $domain->checkoutNewBranch();

        $c1->setAddress("Springs fall 222");
        $model->saveCustomer($c1);
        $c2->setAddress("Barker gulch");
        $model->saveCustomer($c2);

        $specialCommit = $domain->commit("Change of addresses - branch 1");

        $c1->setAddress("Baker straat 221b");
        $model->saveCustomer($c1);
        $c2->setAddress("Cherry blossoms 100");
        $model->saveCustomer($c2);

        $domain->commit("Change of addresses - branch 2");

        // back to master

        $domain->checkoutBranch($domain->getMasterBranch());

        $c1->setAddress("Holy Oak 29");
        $model->saveCustomer($c1);
        $c2->setAddress("Church field 1");
        $model->saveCustomer($c2);

        $domain->commit("Change of addresses - master 2");

        return [$c1, $c2, $specialCommit];
    }
}