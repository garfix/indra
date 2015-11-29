<?php

use indra\definition\TypeDefinition;
use indra\service\Context;
use indra\service\TypeModel;
use my_module\customer\CustomerPicket;
use my_module\customer\CustomerTable;
use my_module\customer\CustomerModel;

require_once __DIR__ . '/TestBase.php';

/**
 * @author Patrick van Bergen
 */
class ViewTest extends TestBase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $typeModel = new TypeModel();

        $type = new TypeDefinition();
        $type->addAttribute('name')->setDataTypeVarchar();

        $typeModel->addType(CustomerPicket::class, $type);
    }

    public function testCreateView()
    {
        $model = new CustomerModel();

        $customer = $model->createCustomer();
        $customer->setName('Dr. Jones');
        $model->saveCustomer($customer);

        // check if table (view) exists and if one row has been added
        $rows = Context::getDB()->queryMultipleRows("
            SELECT " . CustomerTable::NAME .  "
            FROM " . CustomerTable::getTableName() . "
        ");

        $this->assertEquals(1, count($rows));
        $this->assertEquals('Dr. Jones', $rows[0][CustomerTable::NAME]);
    }

    public function testUpdateViewWhenObjectChanges()
    {

    }
}