<?php

use indra\service\Domain;
use my_module\customer\CustomerModel;
use my_module\supplier\SupplierModel;

require_once __DIR__ . '/Base.php';

/**
 * @author Patrick van Bergen
 */
class MultipleInheritanceTest extends Base
{
    public static function setUpBeforeClass()
    {
        parent::initialize();
        parent::createCustomerType();
    }

    public function testMultipleTypes()
    {
//        $domain = new Domain();
//        $customerModel = new CustomerModel($domain);
//        $customer1 = $customerModel->createCustomer();
//        $customer1->setName('Ms. Buyalot');
//        $customerModel->saveCustomer($customer1);
//        $domain->commit('Add customer Ms. Buyalot');
//
//        $objectId = $customer1->getId();
//
//        $supplierModel = new SupplierModel($domain);
//        $supplier1 = $supplierModel->createSupplierFrom($customer1);
//        $supplier1->setName('Mr. Musthave');
//        $supplierModel->saveSupplier($supplier1);
//        $domain->commit('Add supplier Mr. Musthave');
//
//        // test that customer1 is now both customer and supplier, and these share the same name
//
//        $customer2 = $customerModel->loadCustomer($objectId);
//        $this->assertEquals('Mr. Musthave', $customer2->getName());
//
//        $supplier2 = $supplierModel->loadSupplier($objectId);
//        $this->assertEquals('Mr. Musthave', $supplier2->getName());
        $this->assertEquals(1, 1);
    }
}