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

class user extends \Threaded implements \Collectable
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
        $explode = explode(" ", $this->message->content);
        unset($explode[0]);
        $name = implode(" ", $explode);
        $name = stristr($name, "@") ? str_replace("<@", "", str_replace(">", "", $name)) : $name;

        if ($name) {
            $data = $this->db->queryRow("SELECT * FROM users WHERE (nickName = :name OR discordID = :name)", array(":name" => $name));
            if ($data) {
                // Is the person admin?
                $isAdmin = "no";
                foreach ($this->config->get("admins", "permissions") as $admins) {
                    $isAdmin = $admins == $data["discordID"] ? "yes" : "no";
                }

                $msg = "```ID: {$data["discordID"]}\nName: {$data["nickName"]}\nAdmin: {$isAdmin}\nLast Seen: {$data["lastSeen"]}\nLast Spoken: {$data["lastSpoke"]}\nLast Status: {$data["lastStatus"]}\nPlayed Last: {$data["game"]}```";
                $this->message->reply($msg);
            } else {
                $this->message->reply("Error, no such user has been seen..");
            }
        }

        // Mark this as garbage
        $this->isGarbage();
    }
}