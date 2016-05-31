<?php
namespace Sovereign\Lib;

class Config {
    public function __construct() {
    }

    /**
     * @param string $key
     * @param string $type
     * @param string $default
     */
    public function get($key, $type = null, $default = null) {
        $config = array();
        include(BASEDIR . "/config/config.php");

        $type = strtolower($type);
        if (!empty($config[$type][$key])) {
                    return $config[$type][$key];
        }

        return $default;
    }

    public function getAll($type = null) {
        $config = array();
        include(BASEDIR . "/config/config.php");

        $type = strtolower($type);
        if (!empty($config[$type])) {
                    return $config[$type];
        }

        return array();
    }
}