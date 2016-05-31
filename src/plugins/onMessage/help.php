<?php
namespace Sovereign\Plugins\onMessage;

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

/**
 * Class cleverBotMessage
 * @package Sovereign
 */
class help extends \Threaded implements \Collectable
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
    private $onMessagePlugins;
    private $onVoicePlugins;

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
        $this->onMessagePlugins = $extras["onMessagePlugins"];
        $this->onVoicePlugins = $extras["onVoicePlugins"];
    }

    /**
     *
     */
    public function run()
    {
        $explode = explode(" ", $this->message->content);
        $cmd = isset($explode[1]) ? $explode[1] : null;
        $plugins = (object)array_merge((array)$this->onMessagePlugins, (array)$this->onVoicePlugins);
        if (isset($cmd)) {
            foreach ($plugins as $command => $data) {
                if ($command == $cmd) {
                    if ($data["usage"]) {
                                            $this->message->reply("**{$this->channelConfig->prefix}{$command}** _{$data["usage"]}_\r\n {$data["description"]}");
                    } else {
                                            $this->message->reply("**{$this->channelConfig->prefix}{$command}** \r\n {$data["description"]}");
                    }
                }
            }
        } else {
            $msg = "**Commands:** \r\n";
            foreach ($plugins as $command => $data) {
                $msg .= "**{$this->channelConfig->prefix}{$command}** | ";
            }

            $this->message->reply($msg);
        }
    }
}