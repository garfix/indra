<?php

namespace indra\diff;


/**
 * @author Patrick van Bergen
 */
class ObjectAdded extends DiffItem
{
    private $objectId;

    public function __construct($objectId)
    {
        $this->objectId = $objectId;
    }

    /**
     * @return mixed
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

}