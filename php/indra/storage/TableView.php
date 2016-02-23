<?php

namespace indra\storage;

/**
 * @author Patrick van Bergen
 */
interface TableView
{

    /**
     * @return string
     */
    public function getBranchId();

    /**
     * @return string
     */
    public function getTypeId();

    /**
     * @return string
     */
    public function getViewId();

    /**
     * @return string The name of an SQL table
     */
    public function getTableName();
}