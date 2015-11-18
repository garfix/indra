<?php

namespace test;

use indra\service\ClassCreator;
use indra\object\Type;
use indra\service\Context;
use indra\service\TableCreator;
use indra\temp\testdir\Customer;
use indra\temp\testdir\CustomerModel;
use PHPUnit_Framework_TestCase;
use indra\temp\testdir\CustomerPicket;

/**
 * @author Patrick van Bergen
 */
class CreateObjectTest extends PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        require_once __DIR__ . '/../../autoloader.php';

        $tableCreator = new TableCreator();
        $tableCreator->createBasicTables();

        $classCreator = new ClassCreator();

        $type = new Type();
        $type->addAttribute('name');
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
        $customer->setName('Dr. Jones');

        $model->save($customer);

        $id = $customer->getId();

        $customer2 = $model->load($id);
        $name = $customer2->getName();

        $this->assertEquals('Dr. Jones', $name);
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