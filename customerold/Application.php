<?php

namespace my_module;

use my_module\generated_dir\Customer1Type;
use my_module\generated_dir\CustomerModel;

/**
 * @author Patrick van Bergen
 */
class Application
{
    public function start()
    {
        $id = '6767dfsGHJDASKjd234474';

        $model = new CustomerModel();
        $customer = $model->getCustomer($id);

        $name = $customer->getName();

        $customer->setEmail('john@doe.com');

        $model->save($customer);
    }

    public function addCustomer()
    {
        $model = new CustomerModel();
        $customer = $model->createCustomer();

    }

    public function changeType()
    {
        $model = new CustomerModel();
        $customer = $model->getCustomer($id);

        $indra->changeType($customer, Supplier::class);
    }

    public function twoRoles()
    {
        $customerModel = new CustomerModel();
        $customer = $customerModel->getCustomer($id);

        $supplierModel = new SupplierModel();
        $supplier = $supplierModel->createSupplierFromExistingObject($customer, Customer::class);
        $supplierModel->save($supplier);
    }

    public function getAttributes()
    {
        $q = "
          select name, email
          from " . CustomerView::ALL . " name
        ";
    }

    public function multilingual()
    {
        $customerModel = new CustomerModel();
        $customer = $customerModel->getCustomer($id);

        $name = $customer->getName('de_DE');
    }

    public function lists()
    {
        $customerModel = new CustomerModel();
        $customer = $customerModel->getCustomer($id);

        $items = $customer->getItems();
    }
}