<?php

namespace indra\storage;

/**
 * @author Patrick van Bergen
 */
class RevisionAction
{
    const ACTION_ACTIVATE = 'A';
    const ACTION_DEACTIVATE = 'D';

    /** @var  string Indra id */
    private $tripleId;

    /** @var  string */
    private $action;

    public function __construct($tripleId, $action)
    {
        $this->tripleId = $tripleId;
        $this->action = $action;
    }

    /**
     * @return string Indra id
     */
    public function getTripleId()
    {
        return $this->tripleId;
    }

    /**
     * @return string An action id
     */
    public function getAction()
    {
        return $this->action;
    }
}