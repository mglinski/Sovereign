<?php
namespace Sovereign\Lib;

/**
 * Class Config
 * @package Sovereign\Lib
 */
class Config
{
    /**
     * Config constructor.
     */
    public function __construct()
    {
    }

    /**
     * @param string $key
     * @param string $type
     * @param string $default
     */
    public function get($key, $type = null, $default = null)
    {
        $config = array();
        include(BASEDIR . "/config/config.php");

        $type = strtolower($type);
        if (!empty($config[$type][$key])) {
            return $config[$type][$key];
        }

        return $default;
    }

    /**
     * @param null $type
     * @return array
     */
    public function getAll($type = null)
    {
        $config = array();
        include(BASEDIR . "/config/config.php");

        $type = strtolower($type);
        if (!empty($config[$type])) {
            return $config[$type];
        }

        return array();
    }
}