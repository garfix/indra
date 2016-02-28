<?php

namespace test;

use indra\service\Domain;
use indra\storage\Branch;
use my_module\customer\Customer;
use my_module\customer\CustomerModel;

require_once __DIR__ . '/Base.php';

/**
 * @author Patrick van Bergen
 */
class RebaseTest extends  \Base
{
    public static function setUpBeforeClass()
    {
        parent::initialize();
        parent::createCustomerType();
    }

    public function testRebase()
    {
        $domain = new Domain();
        $model = new CustomerModel($domain);

        /** @var Customer $c1 */
        /** @var Customer $c2 */
        /** @var Branch $specialBranch */
        list($c1, $c2, $specialBranch) = $this->setupFixture($domain, $model);

        $domain->checkoutBranch($specialBranch);

        // pre-test
        $d1 = $model->loadCustomer($c1->getId());
        $d2 = $model->loadCustomer($c2->getId());
        $this->assertSame('Baker street 221b', $d1->getAddress());
        $this->assertSame('Cherry blossoms 100', $d2->getAddress());
        $this->assertSame('Harry', $d1->getName());
        $this->assertSame('Sally', $d2->getName());

        // insert all commits of master since the departure before the new commits of specialBranch
        $domain->rebaseBranch($domain->getMasterBranch());

        // post test
        $e1 = $model->loadCustomer($c1->getId());
        $e2 = $model->loadCustomer($c2->getId());
        $this->assertSame('Baker street 221b', $e1->getAddress());
        $this->assertSame('Cherry blossoms 100', $e2->getAddress());
        $this->assertSame('Harry', $e1->getName());
        $this->assertSame('Barry', $e2->getName());
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

        $specialBranch = $domain->checkoutNewBranch();

        $c1->setAddress("Springs fall 222");
        $model->saveCustomer($c1);
        $c2->setAddress("Barker Gulch");
        $model->saveCustomer($c2);

        $specialCommit = $domain->commit("Change of addresses - branch 1");

        $c1->setAddress("Baker street 221b");
        $model->saveCustomer($c1);
        $c2->setAddress("Cherry blossoms 100");
        $model->saveCustomer($c2);

        $domain->commit("Change of addresses - branch 2");

        // back to master

        $domain->checkoutBranch($domain->getMasterBranch());

        $c1->setAddress("Holy Oak 29");
        $model->saveCustomer($c1);
        $c2->setName("Barry");
        $model->saveCustomer($c2);

        $domain->commit("Change of addresses - master 2");

        return [$c1, $c2, $specialBranch];
    }

}