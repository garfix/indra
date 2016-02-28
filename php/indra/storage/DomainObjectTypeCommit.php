<?php

namespace indra\storage;

/**
 * @author Patrick van Bergen
 */
class DomainObjectTypeCommit
{
    private $commitId;

    private $typeId;

    private $diffItems = [];

    public function __construct($commitId, $typeId, array $diffItems)
    {
        $this->commitId = $commitId;
        $this->typeId = $typeId;
        $this->diffItems = $diffItems;
    }

    /**
     * @return string
     */
    public function getCommitId()
    {
        return $this->commitId;
    }

    /**
     * @return string
     */
    public function getTypeId()
    {
        return $this->typeId;
    }

    public function getDiffItems()
    {
        return $this->diffItems;
    }
}