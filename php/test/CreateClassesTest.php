<?php

use indra\definition\AttributeDefinition;
use indra\definition\TypeDefinition;
use indra\service\ClassCreator;
use indra\service\TableCreator;
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
        $product = new Product(new ProductType(), \indra\service\Context::getIdGenerator()->generateId());

        $this->assertEquals(true, $product instanceof Product);
    }
}