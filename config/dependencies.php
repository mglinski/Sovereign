<?php
// Init the container
$container = new \Pimple\Container();

// Add dependencies here
$container["log"] = function($container) {
    $log = new \Monolog\Logger("Sovereign");
    $log->pushHandler(new \Monolog\Handler\StreamHandler("php://stdout", \Monolog\Logger::INFO));

    return $log;
};

$container["config"] = function($container) {
    return new \Sovereign\Lib\Config();
};

$container["db"] = function($container) {
    return new \Sovereign\Lib\Db($container["config"], $container["log"]);
};

$container["curl"] = function($container) {
    return new \Sovereign\Lib\cURL($container["log"]);
};

$container["settings"] = function($container) {
    return new \Sovereign\Lib\Settings($container["db"]);
};

$container["permissions"] = function($container) {
    return new \Sovereign\Lib\Permissions($container["db"], $container["config"]);
};

$container["serverConfig"] = function($container) {
    return new \Sovereign\Lib\ServerConfig($container["db"]);
};

$container["users"] = function($container) {
    return new \Sovereign\Lib\Users($container["db"]);
};

// Keep at the bottom to return the container
return $container;