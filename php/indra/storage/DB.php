<?php

namespace indra\storage;

use indra\exception\DataBaseException;
use indra\service\Context;

/**
 * @author Patrick van Bergen
 */
class DB
{
    public function esc($value)
    {
        $mysqli = Context::getMySqli();

        return mysqli_real_escape_string($mysqli, $value);
    }

    public function execute($query)
    {
        $mysqli = Context::getMySqli();

        $resultSet = $mysqli->query($query);

        if (!$resultSet) {
            throw new DataBaseException("MySQL error: " . mysqli_error($mysqli));
        }
    }

    public function queryMultipleRows($query)
    {
        $mysqli = Context::getMySqli();

        $resultSet = $mysqli->query($query);

        if ($resultSet) {
            while ($result = $resultSet->fetch_assoc()) {
                yield $result;
            }
        } else {
            throw new DataBaseException("MySQL error: " . mysqli_error($mysqli));
        }
    }
}