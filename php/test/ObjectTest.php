<?php

use indra\definition\AttributeDefinition;
use indra\definition\TypeDefinition;
use indra\service\Context;
use indra\service\Domain;
use indra\service\TypeModel;
use my_module\customer\CustomerModel;
use my_module\customer\CustomerPicket;

require_once __DIR__ . '/TestBase.php';

/**
 * @author Patrick van Bergen
 */
class CreateObjectTest extends TestBase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        parent::createCustomerType();
    }

    public function testCreateObject()
    {
        $domain = Domain::loadFromIni();
        $model = new CustomerModel($domain);

        $customer = $model->createCustomer();
        $this->assertNotEmpty($customer->getId());

        $customer->setName('Dr. Jones');
        $model->saveCustomer($customer);
        $id = $customer->getId();

        $customer2 = $model->loadCustomer($id);
        $name = $customer2->getName();
        $this->assertEquals('Dr. Jones', $name);
    }

    public function testUpdateObject()
    {
        $domain = Domain::loadFromIni();
        $model = new CustomerModel($domain);

        $customer = $model->createCustomer();
        $customer->setName('Dr. Jones');
        $model->saveCustomer($customer);
        $id = $customer->getId();

        $customer2 = $model->loadCustomer($id);
        $customer2->setName('Dr. Livingstone');
        $model->saveCustomer($customer2);

        $customer3 = $model->loadCustomer($id);
        $name = $customer3->getName();
        $this->assertEquals('Dr. Livingstone', $name);
    }

    public function testRemoveObject()
    {
        $domain = Domain::loadFromIni();
        $model = new CustomerModel($domain);

        $customer = $model->createCustomer();
        $customer->setName('Dr. Jones');
        $model->saveCustomer($customer);
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