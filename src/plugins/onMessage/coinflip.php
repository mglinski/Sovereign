<?php

namespace Sovereign\Plugins;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Monolog\Logger;
use Sovereign\Lib\Config;
use Sovereign\Lib\cURL;
use Sovereign\Lib\Db;
use Sovereign\Lib\Permissions;
use Sovereign\Lib\ServerConfig;
use Sovereign\Lib\Settings;
use Sovereign\Lib\Users;

class coinflip extends \Threaded implements \Collectable
{
    /**
     * @var Message
     */
    private $message;
    /**
     * @var Discord
     */
    private $discord;
    /**
     * @var Logger
     */
    private $log;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var Db
     */
    private $db;
    /**
     * @var cURL
     */
    private $curl;
    /**
     * @var Settings
     */
    private $settings;
    /**
     * @var Permissions
     */
    private $permissions;
    /**
     * @var ServerConfig
     */
    private $serverConfig;
    /**
     * @var Users
     */
    private $users;
    /**
     * @var \WolframAlpha\Engine
     */
    private $wolframAlpha;
    /**
     * @var int
     */
    private $startTime;

    public function __construct($message, $discord, $log, $config, $db, $curl, $settings, $permissions, $serverConfig, $users, $wolframAlpha, $startTime)
    {
        $this->message = $message;
        $this->discord = $discord;
        $this->log = $log;
        $this->config = $config;
        $this->db = $db;
        $this->curl = $curl;
        $this->settings = $settings;
        $this->permissions = $permissions;
        $this->serverConfig = $serverConfig;
        $this->users = $users;
        $this->wolframAlpha = $wolframAlpha;
        $this->startTime = $startTime;
    }

    public function run()
    {
        $sides = ["Heads", "Tails"];
        $this->message->reply("The result of the coinflip is: " . $sides[array_rand($sides)]);
    }

    public function information()
    {
        return (object)array(
            "description" => "Flips a coin, and gives you the results",
            "usage" => "",
            "permission" => 1//1 is everyone, 2 is only admin
        );
    }
}