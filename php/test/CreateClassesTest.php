<?php

use indra\definition\AttributeDefinition;
use indra\definition\TypeDefinition;
use indra\service\ClassCreator;
use indra\service\Context;
use my_module\product\ProductPicket;
use my_module\product\ProductType;
use my_module\product\Product;

require __DIR__ . '/Base.php';

/**
 * @author Patrick van Bergen
 */
class CreateTypeTest extends Base
{
    public static function setUpBeforeClass()
    {
        parent::initialize();

        @unlink(__DIR__ . '/my_module/product/Product.php');
        @unlink(__DIR__ . '/my_module/product/ProductModel.php');
        @unlink(__DIR__ . '/my_module/product/ProductType.php');
        @unlink(__DIR__ . '/my_module/product/ProductTable.php');
    }

    public function testCreateClasses()
    {
        $classCreator = new ClassCreator();

        $typeDefinition = new TypeDefinition();
        $typeDefinition->addAttribute(AttributeDefinition::create('name')->setDataTypeVarchar());
        $typeDefinition->addAttribute(AttributeDefinition::create('introductionDate')->setDataTypeDate());
        $classCreator->createClasses(ProductPicket::class, $typeDefinition);

        // test if customer class has been created
        // NB: it is correct that this class does not exist at compile time. That's exactly the point :)
        $product = new Product(new ProductType(), Context::getIdGenerator()->generateId(), []);

        $this->assertEquals(true, $product instanceof Product);
    }

    public function testRecreateClasses_shouldChangeFileButLeaveTypeIdUnchanged()
    {
        $classCreator = new ClassCreator();

        $typeDefinition = new TypeDefinition();
        $typeDefinition->addAttribute(AttributeDefinition::create('name')->setDataTypeVarchar());
        $typeDefinition->addAttribute(AttributeDefinition::create('introductionDate')->setDataTypeDate());
        $classCreator->createClasses(ProductPicket::class, $typeDefinition);

        $Class = new ReflectionClass(ProductType::class);
        $fileName = $Class->getFileName();
        $contents1 = file_get_contents($fileName);
        $Type = new ProductType();
        $typeId = $Type->getId();
        $attributes = $Type->getAttributes();
        $firstAttribute = reset($attributes);
        $firstAttributeId = $firstAttribute->getId();

        $typeDefinition->addAttribute(AttributeDefinition::create('price')->setDataTypeInteger());

        $classCreator->createClasses(ProductPicket::class, $typeDefinition);

        $contents2 = file_get_contents($fileName);

        // check if the type id has changed
        $this->assertEquals(1, preg_match('/' . $typeId . '/', $contents2));

        // check if the attribute id has changed
        $this->assertEquals(1, preg_match('/' . $firstAttributeId . '/', $contents2));

        // check if the attribute has been added
        $this->assertTrue(strlen($contents1) < strlen($contents2));

    }
}