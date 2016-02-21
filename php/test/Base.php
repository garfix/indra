<?php

use indra\service\TypeDefinition;
use indra\service\ClassCreator;
use indra\service\Context;
use indra\service\TableCreator;
use my_module\customer\CustomerPicket;
use my_module\supplier\SupplierPicket;

require_once __DIR__ . '/../autoloader.php';
require_once __DIR__ . '/my_module/test_autoloader.php';

/**
 * @author Patrick van Bergen
 */
class Base extends PHPUnit_Framework_TestCase
{
    const REMOVE_GENERATED_CLASSES = true;

    private static $initialized = false;
    private static $classesCreated = false;

    public static function initialize()
    {
        Context::setTestMode(true);

        if (!self::$initialized) {

            if (self::REMOVE_GENERATED_CLASSES) {
                @unlink(__DIR__ . '/my_module/customer/Customer.php');
                @unlink(__DIR__ . '/my_module/customer/CustomerModel.php');
                @unlink(__DIR__ . '/my_module/customer/CustomerType.php');
                @unlink(__DIR__ . '/my_module/customer/CustomerTable.php');
                @unlink(__DIR__ . '/my_module/supplier/Supplier.php');
                @unlink(__DIR__ . '/my_module/supplier/SupplierModel.php');
                @unlink(__DIR__ . '/my_module/supplier/SupplierType.php');
                @unlink(__DIR__ . '/my_module/supplier/SupplierTable.php');
            }

            $tableCreator = new TableCreator();
            $tableCreator->createBasicTables();

            self::$initialized = true;
        }
    }

    protected static function createCustomerType()
    {
        if (!self::$classesCreated) {

            $classCreator = new ClassCreator();

            $type = new TypeDefinition();
            $type->addAttribute('name')->setDataTypeVarchar();
            $type->addAttribute('birthDate')->setDataTypeDate();
            $type->addAttribute('address')->setDataTypeVarchar();
            $classCreator->createClasses(CustomerPicket::class, $type);

            $type = new TypeDefinition();
            $type->addAttribute('name')->setDataTypeVarchar();
            $classCreator->createClasses(SupplierPicket::class, $type);

            self::$classesCreated = true;
        }
    }

    public function setUp()
    {
        $mysqli = Context::getMySqli();
        $mysqli->begin_transaction(true);
    }

    public function test()
    {

    }

    public function tearDown()
    {
        $mysqli = Context::getMySqli();
        $mysqli->rollback();
    }
}