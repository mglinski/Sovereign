<?php
namespace Sovereign\Lib;
use Monolog\Logger;

/**
 * Class Config
 * @package Sovereign\Lib
 */
class Config
{
    /**
     * @var mixed
     */
    private $config;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Config constructor.
     * @param $configFile
     * @param Logger $logger
     */
    public function __construct($configFile, Logger $logger) {
        $this->logger = $logger;
        $this->loadFile($configFile);
    }

    /**
     * @param $configFile
     */
    public function loadFile ($configFile) {

        if (!file_exists(realpath($configFile))) {
            $this->logger->addError('Config file '.realpath($configFile).' not found.');
            return;
        }

        try {
            $this->config = array_change_key_case(include($configFile), \CASE_LOWER);
            $this->logger->addDebug('Config file loaded: '.realpath($configFile));
        } catch (\Exception $e) {
            $this->logger->addError('Failed loading config file ('.realpath($configFile).'): '.$e->getMessage());
        }
    }

    /**
     * @param string $key
     * @param string|null $type
     * @param string|null $default
     * @return null|string
     */
    public function get($key, $type = null, $default = null)
    {
        $type = strtolower($type);
        if (!empty($this->config[$type][$key])) {
            return $this->config[$type][$key];
        }

        $this->logger->addWarning('Config setting not found: ['.$type.']['.$key.']');

        return $default;
    }

    /**
     * @param string|null $type
     * @return array
     */
    public function getAll($type = null)
    {
        $type = strtolower($type);
        if (!empty($this->config[$type])) {
            return $this->config[$type];
        }

        $this->logger->addWarning('Config group not found: ['.$type.']');

        return array();
    }
}
