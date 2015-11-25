<?php

namespace indra\storage;

use Generator;
use indra\exception\DataBaseException;
use indra\service\Context;

/**
 * @author Patrick van Bergen
 */
class DB
{
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
     * @param string $query
     * @throws DataBaseException
     */
    public function execute($query)
    {
        $mysqli = Context::getMySqli();

        $resultSet = $mysqli->query($query);

        if (!$resultSet) {
            throw new DataBaseException("MySQL error: " . mysqli_error($mysqli));
        }
    }

    /**
     * @param string $query
     * @return array Results
     * @throws DataBaseException
     */
    public function queryMultipleRows($query)
    {
        $mysqli = Context::getMySqli();

        $resultSet = $mysqli->query($query);

        if ($resultSet) {
            $rows = [];
            while ($result = $resultSet->fetch_assoc()) {
                $rows[] = $result;
            }
            return $rows;
        } else {
            throw new DataBaseException("MySQL error: " . mysqli_error($mysqli));
        }
    }

    /**
     * @param string $query
     * @return array Results
     * @throws DataBaseException
     */
    public function querySingleColumn($query)
    {
        $mysqli = Context::getMySqli();

        $resultSet = $mysqli->query($query);

        if ($resultSet) {
            $rows = [];
            while ($result = $resultSet->fetch_assoc()) {
                $rows[] = reset($result);
            }
            return $rows;
        } else {
            throw new DataBaseException("MySQL error: " . mysqli_error($mysqli));
        }
    }

    /**
     * @param string $query
     * @return array|null A single row, or null for no results
     * @throws DataBaseException
     */
    public function querySingleRow($query)
    {
        $mysqli = Context::getMySqli();

        $resultSet = $mysqli->query($query);

        if ($resultSet) {
            if ($result = $resultSet->fetch_assoc()) {
                return $result;
            } else {
                return null;
            }
        } else {
            throw new DataBaseException("MySQL error: " . mysqli_error($mysqli));
        }
    }

    /**
     * @param string $query
     * @return mixed A single value
     * @throws DataBaseException
     */
    public function querySingleCell($query)
    {
        $mysqli = Context::getMySqli();

        $resultSet = $mysqli->query($query);

        if ($resultSet) {
            while ($result = $resultSet->fetch_assoc()) {
                return reset($result);
            }
        } else {
            throw new DataBaseException("MySQL error: " . mysqli_error($mysqli));
        }
    }
}