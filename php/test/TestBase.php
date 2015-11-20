<?php

use indra\service\Context;
use indra\service\TableCreator;

/**
 * @author Patrick van Bergen
 */
class TestBase extends PHPUnit_Framework_TestCase
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

    public function tearDown()
    {
        $mysqli = Context::getMySqli();
        $mysqli->rollback();
    }

    public static function tearDownAfterClass()
    {
        @unlink(__DIR__ . '/my_module/customer/Customer.php');
        @unlink(__DIR__ . '/my_module/customer/CustomerModel.php');
        @unlink(__DIR__ . '/my_module/customer/CustomerType.php');

        $mysqli = Context::getMySqli();
        $mysqli->close();
    }
}