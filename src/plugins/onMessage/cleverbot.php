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
class cleverBotMessage extends \Threaded implements \Collectable
{
    /** @var Message $message */
    private $message;
    /** @var Discord $discord */
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
     * cleverBotMessage constructor.
     * @param $message
     * @param $discord
     * @param $log
     * @param $config
     * @param $db
     * @param $curl
     * @param $settings
     * @param $permissions
     * @param $serverConfig
     * @param $users
     */
    public function __construct($message, $discord, $log, $config, $db, $curl, $settings, $permissions, $serverConfig, $users)
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
    }

    /**
     *
     */
    public function run()
    {
        $guildID = $this->message->getFullChannelAttribute()->guild_id;

        $cleverBotNick = $this->db->queryField("SELECT nick FROM cleverbot WHERE serverID = :serverID", "nick", array(":serverID" => $guildID));

        // Simply remove the <id> part of the string, since it seems to make the responses from Cleverbot be less idiotic and terrible..
        $msg = str_replace("<@{$this->discord->getClient()->id}>", "", $this->message->content);
        $response = $this->curl->post("https://cleverbot.io/1.0/ask", array("user" => $this->config->get("user", "cleverbot"), "key" => $this->config->get("key", "cleverbot"), "nick" => $cleverBotNick, "text" => $msg));

        if ($response) {
            $resp = @json_decode($response);
            $reply = isset($resp->response) ? $resp->response : false;
            if ($reply) {
                $this->message->getChannelAttribute()->broadcastTyping();
                $this->message->reply($reply);
            }
        }
    }
}