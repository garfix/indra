<?php

use indra\definition\AttributeDefinition;
use indra\definition\TypeDefinition;
use indra\service\Context;
use indra\service\Domain;
use indra\service\TableCreator;
use indra\service\TypeModel;
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

    public static function initialize()
    {
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
        $domain = new Domain();
        $typeModel = new TypeModel($domain);
        $name = AttributeDefinition::create('name')->setDataTypeVarchar();

        $type = new TypeDefinition();
        $type->addAttribute($name);
        $type->addAttribute(AttributeDefinition::create('birthDate')->setDataTypeDate());
        $typeModel->addType(CustomerPicket::class, $type);

        $type = new TypeDefinition();
        $type->addAttribute($name);
        $typeModel->addType(SupplierPicket::class, $type);
    }

    public function setUp()
    {
        $mysqli = Context::getMySqli();
        $mysqli->autocommit(false);
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