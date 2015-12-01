<?php

use indra\definition\AttributeDefinition;
use indra\definition\TypeDefinition;
use indra\service\ClassCreator;
use indra\service\Context;
use indra\service\Domain;
use indra\service\TypeModel;
use my_module\customer\CustomerModel;
use my_module\customer\CustomerPicket;
use my_module\supplier\SupplierModel;
use my_module\supplier\SupplierPicket;

require_once __DIR__ . '/TestBase.php';

/**
 * @author Patrick van Bergen
 */
class MultipleInheritanceTest extends TestBase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        parent::createCustomerType();
    }

    public function testMultipleTypes()
    {
        $domain = $domain = Domain::loadFromIni();
        $customerModel = new CustomerModel($domain);
        $customer1 = $customerModel->createCustomer();
        $customer1->setName('Ms. Buyalot');
        $customerModel->saveCustomer($customer1);

        $objectId = $customer1->getId();

        $supplierModel = new SupplierModel($domain);
        $supplier1 = $supplierModel->createSupplierFrom($customer1);
        $supplier1->setName('Mr. Musthave');
        $supplierModel->saveSupplier($supplier1);

        // test that customer1 is now both customer and supplier, and these share the same name

        $customer2 = $customerModel->loadCustomer($objectId);
        $this->assertEquals('Mr. Musthave', $customer2->getName());

        $supplier2 = $supplierModel->loadSupplier($objectId);
        $this->assertEquals('Mr. Musthave', $supplier2->getName());
    }
}