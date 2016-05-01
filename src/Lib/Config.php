<?php
namespace Sovereign\Lib;

use Pimple\Container;

class Config {
    private $container;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    public function get($key, $type = null, $default = null) {
        $config = array();
        include(BASEDIR . "/config/config.php");

        $type = strtolower($type);
        if (!empty($config[$type][$key]))
            return $config[$type][$key];

        return $default;
    }

    public function getAll($type = null) {
        $config = array();
        include(BASEDIR . "/config/config.php");

        $type = strtolower($type);
        if (!empty($config[$type]))
            return $config[$type];

        return array();
    }
}