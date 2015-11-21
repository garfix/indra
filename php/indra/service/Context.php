<?php

namespace indra\service;

use Exception;
use indra\exception\DataBaseException;
use indra\storage\DB;
use indra\storage\IdGenerator;
use indra\storage\MySqlTripleStore;
use indra\storage\RandomIdGenerator;
use indra\storage\TripleStore;
use mysqli;

/**
 * This place stores all major dependencies.
 *
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
    
    /** @var  DB */
    private static $db;

    /** @var  TripleStore */
    private static $tripleStore;

    /** @var  IdGenerator */
    private static $idGenerator;

    /**
     * Removes all services. After this, if a new service is requested, it will be created anew.
     */
    public static function resetAllServices()
    {
        self::$tripleStore = null;
        self::$mysqli = null;
        self::$idGenerator = null;
    }

    /**
     * @param IdGenerator $idGenerator
     */
    public static function setIdGenerator(IdGenerator $idGenerator)
    {
        self::$idGenerator = $idGenerator;
    }

    /**
     * @return RandomIdGenerator
     */
    public static function getIdGenerator()
    {
        return self::$idGenerator ?: self::$idGenerator = new RandomIdGenerator();
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
                throw new DataBaseException("Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
            }

            self::$mysqli = $mysqli;

        }

        return self::$mysqli;
    }

    /**
     * @param DB $DB
     */
    public static function setDB(DB $DB)
    {
        self::$db = $DB;
    }

    /**
     * @return DB
     */
    public static function getDB()
    {
        return self::$db ?: self::$db = new DB();
    }

    /**
     * @param TripleStore $tripleStore
     */
    public static function setTripleStore(TripleStore $tripleStore)
    {
        self::$tripleStore = $tripleStore;
    }

    /**
     * @return MySqlTripleStore|TripleStore
     */
    public static function getTripleStore()
    {
        return self::$tripleStore ?: self::$tripleStore = new MySqlTripleStore();
    }
}