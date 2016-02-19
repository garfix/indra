<?php

use indra\service\Context;
use indra\service\Domain;
use my_module\customer\CustomerTable;
use my_module\customer\CustomerModel;

require_once __DIR__ . '/Base.php';

/**
 * @author Patrick van Bergen
 */
class ViewTest extends Base
{
    public static function setUpBeforeClass()
    {
        parent::initialize();
        parent::createCustomerType();
    }

    public function testCreateView()
    {
        $domain = new Domain();
        $model = new CustomerModel($domain);

        $customer = $model->createCustomer();
        $customer->setName('Dr. Jones');
        $model->saveCustomer($customer);
        $domain->commit('Add customer Dr. Jones');

        // check if table (view) exists and if one row has been added
        $rows = Context::getDB()->queryMultipleRows("
            SELECT " . CustomerTable::NAME .  "
            FROM " . $model->getCustomerTable() . "
        ");

        $this->assertEquals(1, count($rows));
        $this->assertEquals('Dr. Jones', $rows[0][CustomerTable::NAME]);
    }

    public function testUpdateViewWhenObjectChanges()
    {

    }
}