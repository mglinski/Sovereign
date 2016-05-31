<?php

namespace Sovereign\Plugins\onMessage;

use DateTime;
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

class about extends \Threaded implements \Collectable
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
     * @var array
     */
    private $channelConfig;
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
     * @var array
     */
    private $extras;

    public function __construct($message, $discord, $channelConfig, $log, $config, $db, $curl, $settings, $permissions, $serverConfig, $users, $extras)
    {
        $this->message = $message;
        $this->discord = $discord;
        $this->channelConfig = $channelConfig;
        $this->log = $log;
        $this->config = $config;
        $this->db = $db;
        $this->curl = $curl;
        $this->settings = $settings;
        $this->permissions = $permissions;
        $this->serverConfig = $serverConfig;
        $this->users = $users;
        $this->extras = $extras;
    }

    public function run()
    {
        $time1 = new DateTime(date("Y-m-d H:i:s", $this->extras["startTime"]));
        $time2 = new DateTime(date("Y-m-d H:i:s"));
        $interval = $time1->diff($time2);

        $msg = "```I am the vanguard of your destruction. This exchange is just beginning...

Author: Karbowiak (Discord ID: 118440839776174081)
Library: DiscordPHP (https://github.com/teamreflex/DiscordPHP\)
Current Version: 0.0000000000
Github Repo: https://github.com/karbowiak/Sovereign\

Statistics:
Guild/Server Count: {$this->extras["guildCount"]} (For specifics use {$this->channelConfig->prefix}guilds)
Member Count: {$this->extras["memberCount"]}
Memory Usage: ~" . round(memory_get_usage() / 1024 / 1024, 3) . "MB
Uptime: " . $interval->y . " Year(s), " . $interval->m . " Month(s), " . $interval->d . " Days, " . $interval->h . " Hours, " . $interval->i . " Minutes, " . $interval->s . " seconds.
```";
        $this->message->reply($msg);

        // Mark this as garbage
        $this->isGarbage();
    }
}