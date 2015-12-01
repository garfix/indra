<?php

namespace indra\service;
use indra\storage\MySqlViewStore;
use indra\storage\ViewStore;

/**
 * @author Patrick van Bergen
 */
class Domain
{

    /** @var  ViewStore */
    private $viewStore;

    /** @var  RevisionModel */
    private $RevisionModel;

    /** @var  BranchModel */
    private $BranchModel;

    public static function loadFromIni()
    {
#todo: load from ini
        return new Domain();
    }

    /**
     * @param ViewStore $viewStore
     */
    public function setViewStore(ViewStore $viewStore)
    {
        $this->viewStore = $viewStore;
    }

    /**
     * @return MySqlViewStore|ViewStore
     */
    public function getViewStore()
    {
        return $this->viewStore ?: $this->viewStore = new MySqlViewStore();
    }

    /**
     * @param RevisionModel $RevisionModel
     */
    public function setRevisionModel(RevisionModel $RevisionModel)
    {
        $this->RevisionModel = $RevisionModel;
    }

    /**
     * @return RevisionModel
     */
    public function getRevisionModel()
    {
        return $this->RevisionModel ?: $this->RevisionModel = new RevisionModel();
    }

    /**
     * @param BranchModel $BranchModel
     */
    public function setBranchModel(BranchModel $BranchModel)
    {
        $this->BranchModel = $BranchModel;
    }

    /**
     * @return BranchModel
     */
    public function getBranchModel()
    {
        return $this->BranchModel ?: $this->BranchModel = new BranchModel();
    }
}