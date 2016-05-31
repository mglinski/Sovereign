<?php

namespace Sovereign\Plugins\onMessage;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\Role;
use Discord\Parts\User\Member;
use Monolog\Logger;
use Sovereign\Lib\Config;
use Sovereign\Lib\cURL;
use Sovereign\Lib\Db;
use Sovereign\Lib\Permissions;
use Sovereign\Lib\ServerConfig;
use Sovereign\Lib\Settings;
use Sovereign\Lib\Users;

class auth extends \Threaded implements \Collectable
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
        $authString = isset($explode[1]) ? $explode[1] : "";

        if ($this->message->getChannelAttribute()->is_private) {
            return $this->message->reply("**Error** You are trying to send your auth token in private. This won't work because i need the guild information, which i can only get if you post it in a public channel on the server you want to get authed on.");
        }

        $authData = $this->db->queryRow("SELECT * FROM authRegs WHERE authString = :authString AND active = 1", array(":authString" => $authString));

        if ($authData) {
            $groups = json_decode($authData["groups"], true);
            $characterID = $authData["characterID"];
            /** @var Role $roles */
            $roles = $this->message->getFullChannelAttribute()->getGuildAttribute()->getRolesAttribute();
            /** @var Member $member */
            $member = $this->message->getFullChannelAttribute()->getGuildAttribute()->getMembersAttribute()->get("id", $this->message->author->id);
            $username = $this->message->author->username;
            $discordID = $this->message->author->id;

            // @todo Force ingame name
            $characterName = json_decode($this->curl->get("https://evedata.xyz/api/character/shortinformation/{$characterID}/"))->characterName;
            // Doesn't work yet, but it should be something like $member->nick($characterName);
            //$member->user->setAttribute("username", $characterName);

            /** @var Role $role */
            foreach ($roles as $role) {
                $roleName = $role->name;
                if (in_array($roleName, $groups)) {
                    $member->addRole($role);
                }
            }

            // Save the member object, so all the roles are set
            $member->save();

            $this->db->execute("UPDATE authRegs SET discordID = :discordID, active = 0 WHERE authString = :authString", array(":discordID" => $discordID, ":authString" => $authString));
            $this->log->addInfo("{$username} authenticated in {$this->message->getChannelAttribute()->name} on {$this->message->getChannelAttribute()->getGuildAttribute()->name}");
            $this->message->reply("You have now been added to the following groups: " . implode(", ", $groups));
        } else {
            $this->message->reply("**Error** You are trying to authenticate with an already used (or not existing) auth token..");
        }

        // Mark this as garbage
        $this->isGarbage();
    }
}