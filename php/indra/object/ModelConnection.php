<?php

namespace indra\object;

/**
 * This class was added to remove some Domain methods that should not be part of its public API.
 *
 * @author Patrick van Bergen
 */
class ModelConnection
{
    /** @var DomainObject[] */
    private $saveList = [];

    /** @var DomainObject[] */
    private $removeList = [];

    /**
     * @return DomainObject[]
     */
    public function getSaveList()
    {
        return $this->saveList;
    }

    /**
     * @param DomainObject $object
     */
    public function addToSaveList(DomainObject $object)
    {
        $this->saveList[] = $object;
    }

    /**
     * @return DomainObject[]
     */
    public function getRemoveList()
    {
        return $this->removeList;
    }

    /**
     * @param DomainObject $object
     */
    public function addToRemoveList(DomainObject $object)
    {
        $this->removeList[] = $object;
    }

    /**
     * Clear the change lists.
     */
    public function clear()
    {
        $this->saveList = [];
        $this->removeList = [];
    }
}