<?php

use indra\service\Domain;
use my_module\customer\CustomerModel;

require_once __DIR__ . '/Base.php';

/**
 * @author Patrick van Bergen
 */
class CreateObjectTest extends Base
{
    public static function setUpBeforeClass()
    {
        parent::initialize();
        parent::createCustomerType();
    }

    public function testCreateObject()
    {
        $domain = new Domain();
        $model = new CustomerModel($domain);

        $customer = $model->createCustomer();
        $this->assertNotEmpty($customer->getId());

        $customer->setName('Dr. Jones');
        $model->saveCustomer($customer);
        $domain->commit('Add customer Dr. Jones');
        $id = $customer->getId();

        $customer2 = $model->loadCustomer($id);
        $name = $customer2->getName();
        $this->assertEquals('Dr. Jones', $name);
    }

    public function testUpdateObject()
    {
        $domain = new Domain();
        $model = new CustomerModel($domain);

        $customer = $model->createCustomer();
        $customer->setName('Dr. Jones');
        $model->saveCustomer($customer);
        $domain->commit('Add customer Dr. Jones');
        $id = $customer->getId();

        $customer2 = $model->loadCustomer($id);
        $customer2->setName('Dr. Livingstone');
        $model->saveCustomer($customer2);
        $domain->commit('Add customer Dr. Livingstone');

        $customer3 = $model->loadCustomer($id);
        $name = $customer3->getName();
        $this->assertEquals('Dr. Livingstone', $name);
    }

    public function testRemoveObject()
    {
        $domain = new Domain();
        $model = new CustomerModel($domain);

        $customer = $model->createCustomer();
        $customer->setName('Dr. Jones');
        $model->saveCustomer($customer);
        $domain->commit('Add customer Dr. Jones');
        $id = $customer->getId();

        $model->removeCustomer($customer);

        $exception = false;

        try {
            $model->loadCustomer($id);
        } catch (Exception $e) {
            $exception = true;
        }

        $this->assertTrue($exception);
    }
}