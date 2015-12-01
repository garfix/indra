<?php

use indra\definition\AttributeDefinition;
use indra\definition\TypeDefinition;
use indra\service\Context;
use indra\service\Domain;
use indra\service\RevisionModel;
use indra\service\TypeModel;
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
        parent::createCustomerType();
    }

    public function testUndo()
    {
        $domain = Domain::loadFromIni();
        $revisionModel = $domain->getRevisionModel();
        $customerModel = new CustomerModel($domain);

        $customer = $customerModel->createCustomer();
        $id = $customer->getId();

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

#todo: saveCustomer niet toegestaan in revision model wereld
}
