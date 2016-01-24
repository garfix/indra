<?php

namespace indra\storage;

use indra\exception\DataBaseException;
use indra\service\Context;

/**
 * @author Patrick van Bergen
 */
class DB
{
    /** @var  bool */
    private $echoQueries;

    /**
     * @param $echo
     */
    public function setEchoQueries($echo = true)
    {
        $this->echoQueries = $echo;
    }

    /**
     * @param mixed $value
     * @return string
     * @throws DataBaseException
     */
    public function esc($value)
    {
        $mysqli = Context::getMySqli();

        return mysqli_real_escape_string($mysqli, $value);
    }

    /**
     * @param $query
     * @return bool|\mysqli_result
     * @throws DataBaseException
     */
    private function query($query)
    {
        if ($this->echoQueries) {
            var_dump($query);
        }

        return Context::getMySqli()->query($query);
    }

    /**
     * @param string $query
     * @throws DataBaseException
     */
    public function execute($query)
    {
        $resultSet = $this->query($query);

        if (!$resultSet) {
            throw new DataBaseException("MySQL error: " . mysqli_error(Context::getMySqli()));
        }
    }

    /**
     * @param string $query
     * @return array Results
     * @throws DataBaseException
     */
    public function queryMultipleRows($query)
    {
        $resultSet = $this->query($query);

        if ($resultSet) {
            $rows = [];
            while ($result = $resultSet->fetch_assoc()) {
                $rows[] = $result;
            }
            return $rows;
        } else {
            throw new DataBaseException("MySQL error: " . mysqli_error(Context::getMySqli()));
        }
    }

    /**
     * @param string $query
     * @return array Results
     * @throws DataBaseException
     */
    public function querySingleColumn($query)
    {
        $resultSet = $this->query($query);

        if ($resultSet) {
            $rows = [];
            while ($result = $resultSet->fetch_assoc()) {
                $rows[] = reset($result);
            }
            return $rows;
        } else {
            throw new DataBaseException("MySQL error: " . mysqli_error(Context::getMySqli()));
        }
    }

    /**
     * @param string $query
     * @return array|null A single row, or null for no results
     * @throws DataBaseException
     */
    public function querySingleRow($query)
    {
        $resultSet = $this->query($query);

        if ($resultSet) {
            if ($result = $resultSet->fetch_assoc()) {
                return $result;
            } else {
                return null;
            }
        } else {
            throw new DataBaseException("MySQL error: " . mysqli_error(Context::getMySqli()));
        }
    }

    /**
     * @param string $query
     * @return mixed A single value
     * @throws DataBaseException
     */
    public function querySingleCell($query)
    {
        $resultSet = $this->query($query);

        if ($resultSet) {
            while ($result = $resultSet->fetch_assoc()) {
                return reset($result);
            }
            return null;
        }

        throw new DataBaseException("MySQL error: " . mysqli_error(Context::getMySqli()));
    }
}