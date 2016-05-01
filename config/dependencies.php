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
    return new \Sovereign\Lib\Config($container);
};

$container["db"] = function($container) {
    return new \Sovereign\Lib\Db($container["config"], $container["log"]);
};

$container["curl"] = function($container) {
    return new \Sovereign\Lib\cURL($container["log"]);
};

// Keep at the bottom to return the container
return $container;