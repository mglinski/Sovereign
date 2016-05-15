<?php
namespace Sovereign\Lib;

use Monolog\Logger;

class Db
{
    protected $config;
    protected $log;
    private $pdo;

    public function __sleep() {
        return array();
    }

    public function __wakeup() {
        $this->log = new \Monolog\Logger("Sovereign");
        $this->log->pushHandler(new \Monolog\Handler\StreamHandler("php://stdout", \Monolog\Logger::INFO));
        $this->config = new Config();
        $this->pdo = $this->connect();
    }

    public function __construct(Config $config, Logger $log) {
        $this->log = $log;
        $this->config = $config;
        $this->pdo = $this->connect();
    }

    private function connect() {
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
            $this->log->addCritical("Unable to connect to database", [$e->getMessage()]);
            die();
        }

        return $pdo;
    }

    public function query(String $query, $parameters = array()) {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($parameters);

            if ($stmt->errorCode() != 0)
                return array();

            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $stmt->closeCursor();

            return $result;
        } catch (\Exception $e) {
            $this->log->addError("There was an error during a query", [$e->getMessage()]);
            try {
                $this->pdo = $this->connect();
            } catch (\Exception $e2) {
                $this->log->addCritical("Couldn't reconnect to the database: " . $e->getMessage());
                die(1);
            }
        }
        return array();
    }

    public function queryRow(String $query, $parameters = array()) {
        $result = $this->query($query, $parameters);

        if(count($result) >= 1)
            return $result[0];

        return array();
    }

    public function queryField(String $query, String $field, $parameters = array()) {
        $result = $this->query($query, $parameters);

        if(count($result) == 0)
            return "";

        $resultRow = $result[0];
        return $resultRow[$field];
    }

    public function execute(String $query, $parameters = array()) {
        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare($query);
            $stmt->execute($parameters);

            if($stmt->errorCode() != 0) {
                $this->pdo->rollBack();
                return 0;
            }

            $returnID = $this->pdo->lastInsertId();
            $this->pdo->commit();
            $stmt->closeCursor();

            return $returnID;
        } catch (\Exception $e) {
            $this->log->addError("There was an error during a query", [$e->getMessage()]);
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