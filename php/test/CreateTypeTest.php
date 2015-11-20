<?php

namespace test;

use indra\service\ClassCreator;
use indra\object\Type;
use indra\service\Context;
use indra\service\TableCreator;
use my_module\customer\CustomerType;
use PHPUnit_Framework_TestCase;
use my_module\customer\CustomerPicket;
use my_module\customer\Customer;

/**
 * @author Patrick van Bergen
 */
class CreateTypeTest extends PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        require_once __DIR__ . '/../autoloader.php';
        require_once __DIR__ . '/my_module/test_autoloader.php';

        $tableCreator = new TableCreator();
        $tableCreator->createBasicTables();
    }

    public function setUp()
    {
        $mysqli = Context::getMySqli();
        $mysqli->autocommit(false);
    }

    public function testCreateClasses()
    {
        $classCreator = new ClassCreator();

        $type = new Type();
        $type->addAttribute('name')->setDataTypeVarchar();
        $classCreator->createClasses(CustomerPicket::class, $type);

        // test if customer class has been created
        // NB: it is correct that this class does not exist at compile time. That's exactly the point :)
        $customer = new Customer(new CustomerType());

        $this->assertEquals(true, $customer instanceof Customer);

        unlink(__DIR__ . '/my_module/customer/Customer.php');
        unlink(__DIR__ . '/my_module/customer/CustomerModel.php');
        unlink(__DIR__ . '/my_module/customer/CustomerType.php');
    }

    public function tearDown()
    {
        $mysqli = Context::getMySqli();
        $mysqli->rollback();
    }

    public static function tearDownAfterClass()
    {
        $mysqli = Context::getMySqli();
        $mysqli->close();
    }
}