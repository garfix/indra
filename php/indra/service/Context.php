<?php

namespace indra\service;

use Exception;
use indra\exception\DataBaseException;
use indra\storage\DateTimeGenerator;
use indra\storage\DB;
use indra\storage\IdGenerator;
use indra\storage\MySqlPersistenceStore;
use indra\storage\RandomIdGenerator;
use indra\storage\PersistenceStore;
use indra\storage\UserNameProvider;
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

    /** @var bool  */
    private static $testMode = false;

    /** @var  PersistenceStore */
    private static $persistenceStore;

    /** @var  IdGenerator */
    private static $idGenerator;

    /** @var  DateTimeGenerator */
    private static $dateTimeGenerator;

    /** @var  UserNameProvider */
    private static $userNameProvider;

    /**
     * Removes all services. After this, if a new service is requested, it will be created anew.
     */
    public function resetAllServices()
    {
        self::$persistenceStore = null;
        self::$mysqli = null;
        self::$idGenerator = null;
        self::$dateTimeGenerator = null;
        self::$db = null;
        self::$userNameProvider = null;
    }

    public static function setTestMode($testMode)
    {
        self::$testMode = $testMode;
    }

    public static function inTestMode()
    {
        return self::$testMode;
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
     * @param DateTimeGenerator $dateTimeGenerator
     */
    public static function setDateTimeGenerator(DateTimeGenerator $dateTimeGenerator)
    {
        self::$dateTimeGenerator = $dateTimeGenerator;
    }
    
    /**
     * @return DateTimeGenerator
     */
    public static function getDateTimeGenerator()
    {
        return self::$dateTimeGenerator ?: self::$dateTimeGenerator = new DateTimeGenerator();
    }

    /**
     * @param UserNameProvider $userNameProvider
     */
    public static function setUserNameProvider(UserNameProvider $userNameProvider)
    {
        self::$userNameProvider = $userNameProvider;
    }

    /**
     * @return UserNameProvider
     */
    public static function getUserNameProvider()
    {
        return self::$userNameProvider ?: self::$userNameProvider = new UserNameProvider();
    }
    
    /**
     * @param mysqli $mysqli
     */
    public static function setMySqli(mysqli $mysqli = null)
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
     * @param PersistenceStore $persistenceStore
     */
    public static function setPersistenceStore(PersistenceStore $persistenceStore)
    {
        self::$persistenceStore = $persistenceStore;
    }

    /**
     * @return MySqlPersistenceStore|PersistenceStore
     */
    public static function getPersistenceStore()
    {
        return self::$persistenceStore ?: self::$persistenceStore = new MySqlPersistenceStore();
    }
}