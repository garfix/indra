<?php

namespace indra\service;

use Exception;
use indra\storage\MySqlTripleStore;
use indra\storage\TripleStore;
use mysqli;

/**
 * This place stores all major dependencies.
 * It is an implementation of the Ambient Context dependency pattern.
 * The class should not be used to add variable dependencies, to avoid the Service Locator pattern.
 *
 * All other classes may use this class to find default dependencies.
 *
 * @author Patrick van Bergen
 */
class Context
{
    /** @var  mysqli */
    private static $mysqli;

    /** @var  TripleStore */
    private static $TripleStore;

    /**
     * Removes all services. After this, if a new service is requested, it will be created anew.
     */
    public static function resetAllServices()
    {
        self::$TripleStore = null;
        self::$mysqli = null;
    }

    /**
     * @param mysqli $mysqli
     */
    public static function setMySqli(mysqli $mysqli)
    {
        self::$mysqli = $mysqli;
    }

    /**
     * @return mysqli
     * @throws Exception
     */
    public static function getMySqli()
    {
        if (!self::$mysqli) {

            $ini = parse_ini_file(__DIR__ . '/../../../config.ini', true);

            $mysqli = new mysqli($ini['database']['host'], $ini['database']['username'], $ini['database']['password'], $ini['database']['db']);

            if ($mysqli->connect_errno) {
                throw new Exception("Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
            }

            self::$mysqli = $mysqli;

        }

        return self::$mysqli;
    }

    /**
     * @param TripleStore $TripleStore
     */
    public static function setTripleStore(TripleStore $TripleStore)
    {
        self::$TripleStore = $TripleStore;
    }

    /**
     * @return MySqlTripleStore|TripleStore
     */
    public static function getTripleStore()
    {
        return self::$TripleStore ?: self::$TripleStore = new MySqlTripleStore();
    }
}