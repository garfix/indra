<?php

namespace test;

use indra\service\ClassCreator;
use indra\object\Type;
use indra\service\Context;
use indra\service\TableCreator;
use my_module\customer\Customer;
use my_module\customer\CustomerModel;
use PHPUnit_Framework_TestCase;
use my_module\customer\CustomerPicket;

/**
 * @author Patrick van Bergen
 */
class CreateObjectTest extends PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        require_once __DIR__ . '/../autoloader.php';
        require_once __DIR__ . '/my_module/test_autoloader.php';

        $tableCreator = new TableCreator();
        $tableCreator->createBasicTables();

        $type = new Type();
        $type->addAttribute('name');

        $classCreator = new ClassCreator();
        $classCreator->createClasses(CustomerPicket::class, $type);
    }

    public function setUp()
    {
        $mysqli = Context::getMySqli();
        $mysqli->autocommit(false);
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

    public function tearDown()
    {
        $mysqli = Context::getMySqli();
        $mysqli->rollback();
    }

    public static function tearDownAfterClass()
    {
//        unlink(__DIR__ . '/../temp/testdir/Customer.php');
//        unlink(__DIR__ . '/../temp/testdir/CustomerModel.php');
//        unlink(__DIR__ . '/../temp/testdir/CustomerType.php');

        $mysqli = Context::getMySqli();
        $mysqli->close();
    }
}