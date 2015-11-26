<?php

use indra\definition\AttributeDefinition;
use indra\definition\TypeDefinition;
use indra\service\ClassCreator;
use indra\service\RevisionModel;
use my_module\customer\CustomerModel;
use my_module\customer\CustomerPicket;

require_once __DIR__ . '/TestBase.php';

/**
 * @author Patrick van Bergen
 */
class RevisionTest extends TestBase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $classCreator = new ClassCreator();

        $type = new TypeDefinition();
        $type->addAttribute(AttributeDefinition::create('name')
            ->setDataTypeVarchar());
        $classCreator->createClasses(CustomerPicket::class, $type);
    }

    public function testUndo()
    {
        $customerModel = new CustomerModel();
        $customer = $customerModel->createCustomer();
        $id = $customer->getId();

        $revisionModel = new RevisionModel();

        // initial name
        $revision = $revisionModel->createRevision('Add customer Dr. Jones');
        $customer->setName('Dr. Jones');
        $revision->addToSaveList($customer);
        $revisionModel->saveRevision($revision);

        // change name
        $revision = $revisionModel->createRevision('Dr. Jones renamed to Dr. Who');
        $customer->setName('Dr. Who');
        $revision->addToSaveList($customer);
        $revisionModel->saveRevision($revision);

        // revert change
        $undoRevision = $revisionModel->revertRevision($revision);

        // test revert
        $customer2 = $customerModel->loadCustomer($id);
        $name = $customer2->getName();
        $this->assertEquals('Dr. Jones', $name);

        // revert the revert
        $revisionModel->revertRevision($undoRevision);


        // test revert revert
        $customer3 = $customerModel->loadCustomer($id);
        $name = $customer3->getName();
        $this->assertEquals('Dr. Who', $name);
    }

#todo: undo must be a new revision?
#todo: saveCustomer niet toegestaan in revision model wereld
}
