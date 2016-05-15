<?php

namespace Sovereign\Plugins;

use DateTime;
use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\Guild;
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
        $time1 = new DateTime(date("Y-m-d H:i:s", $this->startTime));
        $time2 = new DateTime(date("Y-m-d H:i:s"));
        $interval = $time1->diff($time2);

        $memberCount = 0;
        /** @var Guild $guild */
        foreach ($this->discord->getClient()->getGuildsAttribute()->all() as $guild) {
            var_dump($guild->getMembersAttribute()->count());
            $memberCount += $guild->member_count;
        }

        $msg = "```I am the vanguard of your destruction. This exchange is just beginning...

Author: Karbowiak (Discord ID: 118440839776174081)
Library: DiscordPHP (https://github.com/teamreflex/DiscordPHP\)
Current Version: 0.0000000000
Github Repo: https://github.com/karbowiak/Sovereign\

Statistics:
Server Count: {$this->discord->guilds->count()}
Member Count: {$memberCount} 
Memory Usage: ~" . round(memory_get_usage() / 1024 / 1024, 3) . "MB
Uptime: " . $interval->y . " Year(s), " . $interval->m . " Month(s), " . $interval->d . " Days, " . $interval->h . " Hours, " . $interval->i . " Minutes, " . $interval->s . " seconds.
```";
        $this->message->reply($msg);
    }

    public function information()
    {
        return (object)array(
            "description" => "Shows information about the bot and it's creator",
            "usage" => "",
            "permission" => 1//1 is everyone, 2 is only admin
        );
    }
}