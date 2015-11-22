<?php

use indra\service\Context;
use indra\service\TableCreator;

/**
 * @author Patrick van Bergen
 */
class TestBase extends PHPUnit_Framework_TestCase
{
    const REMOVE_GENERATED_CLASSES = true;

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

    public function tearDown()
    {
        $mysqli = Context::getMySqli();
        $mysqli->rollback();
    }

    public static function tearDownAfterClass()
    {
        if (self::REMOVE_GENERATED_CLASSES) {
            @unlink(__DIR__ . '/my_module/customer/Customer.php');
            @unlink(__DIR__ . '/my_module/customer/CustomerModel.php');
            @unlink(__DIR__ . '/my_module/customer/CustomerType.php');
            @unlink(__DIR__ . '/my_module/supplier/Supplier.php');
            @unlink(__DIR__ . '/my_module/supplier/SupplierModel.php');
            @unlink(__DIR__ . '/my_module/supplier/SupplierType.php');
        }

        $mysqli = Context::getMySqli();
        $mysqli->close();
    }
}