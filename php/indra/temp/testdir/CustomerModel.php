<?php

namespace indra\temp\testdir;

use indra\service\Context;

/**
 * This class was auto-generated. Do not change it, for it will be overwritten.
 */
class CustomerModel
{
    /**
     * @return Customer
     */
    public function create()
    {
        return new Customer($this->getType());
    }

    public function save(Customer $instance)
    {
        Context::getTripleStore()->save($instance);
    }

    public function load($indraId)
    {
        $object = $this->create();
        Context::getTripleStore()->load($object, $indraId);
        return $object;
    }

    private $type;

    private function getType()
    {
        return $this->type ?: $this->type = new CustomerType;
    }
}
