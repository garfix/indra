<?php

use indra\object\Type;
use indra\service\ClassCreator;
use my_module\customer\CustomerModel;
use my_module\customer\CustomerPicket;

require __DIR__ . '/TestBase.php';

/**
 * @author Patrick van Bergen
 */
class CreateObjectTest extends TestBase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $classCreator = new ClassCreator();

        $type = new Type();
        $type->addAttribute('name')->setDataTypeVarchar();
        $classCreator->createClasses(CustomerPicket::class, $type);
    }

    public function testCreateObject()
    {
        $model = new CustomerModel();

        $customer = $model->create();
        $this->assertNotEmpty($customer->getId());

        $customer->setName('Dr. Jones');
        $model->save($customer);
        $id = $customer->getId();

        $customer2 = $model->load($id);
        $name = $customer2->getName();
        $this->assertEquals('Dr. Jones', $name);
    }

    public function testUpdateObject()
    {
        $model = new CustomerModel();

        $customer = $model->create();
        $customer->setName('Dr. Jones');
        $model->save($customer);
        $id = $customer->getId();

        $customer2 = $model->load($id);
        $customer2->setName('Dr. Livingstone');
        $model->save($customer2);

        $customer3 = $model->load($id);
        $name = $customer3->getName();
        $this->assertEquals('Dr. Livingstone', $name);
    }

}