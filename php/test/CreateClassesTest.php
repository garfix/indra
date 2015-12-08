<?php

use indra\definition\AttributeDefinition;
use indra\definition\TypeDefinition;
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

        $typeDefinition = new TypeDefinition();
        $typeDefinition->addAttribute(AttributeDefinition::create('name')->setDataTypeVarchar());
        $classCreator->createClasses(CustomerPicket::class, $typeDefinition);

        // test if customer class has been created
        // NB: it is correct that this class does not exist at compile time. That's exactly the point :)
        $customer = new Customer(new CustomerType(), \indra\service\Context::getIdGenerator()->generateId());

        $this->assertEquals(true, $customer instanceof Customer);
    }
}