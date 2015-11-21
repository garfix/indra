<?php

use indra\service\ClassCreator;
use my_module\customer\CustomerPicket;
use my_module\customer\CustomerType;
use my_module\customer\Customer;

require __DIR__ . '/TestBase.php';

/**
 * @author Patrick van Bergen
 */
class CreateTypeTest extends TestBase
{
    public function testCreateClasses()
    {
        $classCreator = new ClassCreator();

        $type = new \indra\object\TypeDefinition();
        $type->addAttribute('name')->setDataTypeVarchar();
        $classCreator->createClasses(CustomerPicket::class, $type);

        // test if customer class has been created
        // NB: it is correct that this class does not exist at compile time. That's exactly the point :)
        $customer = new Customer(new CustomerType());

        $this->assertEquals(true, $customer instanceof Customer);
    }
}