<?php

use indra\definition\AttributeDefinition;
use indra\definition\TypeDefinition;
use indra\object\Attribute;
use indra\service\ClassCreator;
use my_module\customer\CustomerModel;
use my_module\customer\CustomerPicket;
use my_module\supplier\SupplierModel;
use my_module\supplier\SupplierPicket;

require __DIR__ . '/TestBase.php';

/**
 * @author Patrick van Bergen
 */
class MultipleInheritanceTest extends TestBase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $classCreator = new ClassCreator();

        $name = AttributeDefinition::create('name')
            ->setDataTypeVarchar();

        $type = new TypeDefinition();
        $type->addAttribute($name);
        $classCreator->createClasses(CustomerPicket::class, $type);

        $type = new TypeDefinition();
        $type->addAttribute($name);
        $classCreator->createClasses(SupplierPicket::class, $type);
    }

    public function testMultipleTypes()
    {
        $customerModel = new CustomerModel();
        $customer1 = $customerModel->createCustomer();
        $customer1->setName('Ms. Buyalot');
        $customerModel->saveCustomer($customer1);

        $objectId = $customer1->getId();

        $supplierModel = new SupplierModel();
        $supplier1 = $supplierModel->createSupplierFrom($customer1);
        $supplier1->setName('Mr. Musthave');
        $supplierModel->saveSupplier($supplier1);

        // test that customer1 is now both customer and supplier, and these share the same name

        $customer2 = $customerModel->loadCustomer($objectId);
        $this->assertEquals('Ms. Buyalot', $customer2->getName());

        $supplier2 = $supplierModel->loadSupplier($objectId);
//        $this->assertEquals('Mr. Musthave', $supplier2->getName());
    }
}