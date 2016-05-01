<?php
namespace Sovereign;


use Discord\Discord;
use Discord\WebSockets\Event;
use Discord\WebSockets\WebSocket;
use Monolog\Logger;
use Pimple\Container;
use Sovereign\Lib\Config;

/**
 * Class Sovereign
 * @package Sovereign
 */
class Sovereign {
    /**
     * @var WebSocket
     */
    public $websocket;
    /**
     * @var Discord
     */
    protected $discord;
    /**
     * @var
     */
    public $voice;
    /**
     * @var Container
     */
    protected $container;
    /**
     * @var Logger
     */
    protected $log;
    /**
     * @var Config
     */
    protected $config;

    /**
     * Sovereign constructor.
     * @param Container $container
     */
    public function __construct(Container $container) {
        $this->container = $container;
        $this->log = $container["log"];
        $this->config = $container["config"];

        // Load the plugins

        // Fire up the onStart plugins
        
        // Init Discord and Websocket
        $this->log->addInfo("Initializing Discord and Websocket connections..");
        $this->discord = Discord::createWithBotToken($this->config->get("token", "bot"));
        $this->websocket = new WebSocket($this->discord);
    }

    /**
     * Return a dependency from the container
     * @param $name
     * @return null
     */
    public function __get($name)
    {
        if(isset($this->container[$name]))
            return $this->container[$name];
        return null;
    }

    public function run() {
        $this->websocket->on("ready", function(Discord $discord) {
            $this->log->addInfo("Websocket connected..");
            
            // Update our presence status
            $discord->updatePresence($this->websocket, $this->config->get("presense", "bot", "table flippin'"), false);

            // Setup the timers for the timer plugins
        });

        $this->websocket->on("error", function($error, $websocket) {
            $this->log->addError("An error occured on the websocket", [$error->getMessage()]);
        });

        $this->websocket->on("heartbeat", function($epoch) {
            $this->log->addInfo("Heartbeat at", [$epoch]);
        });

        $this->websocket->on("close", function($opCode, $reason) {
            $this->log->addWarning("Websocket got closed", ["code" => $opCode, "reason" => $reason]);
        });

        $this->websocket->on("reconnecting", function() {
            $this->log->addInfo("Websocket is reconnecting..");
        });
        
        $this->websocket->on("reconnected", function() {
            $this->log->addInfo("Websocket was reconnected..");
        });

        // Handle messages
        $this->websocket->on(Event::MESSAGE_CREATE, function() {

        });

        $this->websocket->run();
    }
}