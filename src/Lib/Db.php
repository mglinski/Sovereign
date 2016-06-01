<?php
namespace Sovereign\Lib;

use Sovereign\Lib\ContainerSingleton;
use League\Container\Container;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * Class Db
 * @package Sovereign\Lib
 */
class Db
{
    /**
     * @var Config
     */
    protected $config;
    /**
     * @var Logger
     */
    protected $log;
    /**
     * @var \PDO
     */
    private $pdo;
    /**
     * @var Container
     */
    private $container;

    /**
     * Db constructor.
     * @param Config $config
     * @param Logger $log
     * @param Container $container
     */
    public function __construct(Config $config, Logger $log, Container $container)
    {
        $this->log = $log;
        $this->config = $config;
        $this->pdo = $this->connect();
        $this->container = $container;
    }

    /**
     * @return array
     * Upon sleeping (which is needed for pthreads to work with pdo) we just return an empty array, which is the default for all the query functions anyway
     * It doesn't really matter what we return tho, since once the thread wakes up, the __wakeup function is ran
     */
    public function __sleep()
    {
        return array();
    }

    /**
     * This is for the pthreads compatibility - for some reason the DB just goes tits up when using pthreads
     * and PDO.. Hence the __wakeup() call, that restarts the database.
     * No numbers on it, but it more than likely adds quite a bit of latency.
     */
    public function __wakeup()
    {
        $this->container = ContainerSingleton::getInstance()->getContainerInstance();
        $this->log = $this->container->get('log');
        $this->config = $this->container->get('config');
        $this->pdo = $this->connect();
    }

    /**
     * @return \PDO
     */
    private function connect()
    {
        $dsn = "mysql:dbname={$this->config->get("dbName", "db")};host={$this->config->get("dbHost", "db")}";
        try {
            $pdo = new \PDO($dsn, $this->config->get("dbUser", "db"), $this->config->get("dbPass", "db"), array(
                \PDO::ATTR_PERSISTENT => false,
                \PDO::ATTR_EMULATE_PREPARES => true,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+00:00',NAMES utf8;"
            ));
        } catch (\Exception $e) {
            $this->log->addCritical("Unable to connect to database: ", [$e->getMessage()]);
            die();
        }

        return $pdo;
    }

    /**
     * @param String $query
     * @param array $parameters
     * @return array
     */
    public function queryRow(String $query, $parameters = array())
    {
        $result = $this->query($query, $parameters);

        if (count($result) >= 1) {
            return $result[0];

    }
        return array();
    }

    /**
     * @param String $query
     * @param array $parameters
     * @return array
     */
    public function query(String $query, $parameters = array())
    {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($parameters);

            if ($stmt->errorCode() != 0) {
                return array();
            }

            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $stmt->closeCursor();

            return $result;
        } catch (\Exception $e) {
            $this->log->addError("There was an error during a query: ", [$e->getMessage()]);
            try {
                $this->pdo = $this->connect();
            } catch (\Exception $e2) {
                $this->log->addCritical("Couldn't reconnect to the database: " . $e->getMessage());
                die(1);
            }
        }
        return array();
    }

    /**
     * @param String $query
     * @param String $field
     * @param array $parameters
     * @return string
     */
    public function queryField(String $query, String $field, $parameters = array())
    {
        $result = $this->query($query, $parameters);

        if (count($result) == 0) {
            return "";
        }

        $resultRow = $result[0];
        return $resultRow[$field];
    }

    /**
     * @param String $query
     * @param array $parameters
     * @return int|null|string
     */
    public function execute(String $query, $parameters = array())
    {
        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare($query);
            $stmt->execute($parameters);

            if ($stmt->errorCode() != 0) {
                $this->pdo->rollBack();
                return 0;
            }

            $returnID = $this->pdo->lastInsertId();
            $this->pdo->commit();
            $stmt->closeCursor();

            return $returnID;
        } catch (\Exception $e) {
            $this->log->addError("There was an error during a query: ", [$e->getMessage()]);
            try {
                $this->pdo = $this->connect();
            } catch (\Exception $e2) {
                $this->log->addCritical("Couldn't reconnect to the database: " . $e->getMessage());
                die(1);
            }
        }
        return null;
    }
}
