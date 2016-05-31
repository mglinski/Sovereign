<?php

namespace Sovereign\Plugins\onMessage;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Monolog\Logger;
use Sovereign\Lib\cURL;
use Sovereign\Lib\Db;
use Sovereign\Lib\Permissions;
use Sovereign\Lib\ServerConfig;
use Sovereign\Lib\Settings;
use Sovereign\Lib\Users;

class config extends \Threaded implements \Collectable
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
        $guildID = $this->message->full_channel->guild->id;
        $input = explode(" ", $this->message->content);
        unset($input[0]);
        $type = isset($input[1]) ? $input[1] : "";
        unset($input[1]);

        // Defaults
        $channelID = $this->message->channel_id;
        $msg = "";

        // Config options
        switch (trim($type)) {
            case "setTrigger":
                $trigger = $input[2];
                $orgTrigger = $this->serverConfig->get($guildID, "prefix") ? $this->serverConfig->get($guildID, "prefix") : $this->channelConfig->prefix;
                $this->serverConfig->set($guildID, "prefix", $trigger);
                $msg = "Trigger has been changed from {$orgTrigger} to {$trigger}";
                break;
            case "enablePorn":
                $pornArray = $this->serverConfig->getAll($guildID)->porn->allowedChannels;
                if (!in_array($channelID, $pornArray)) {
                    $pornArray[] = $channelID;
                }

                $this->serverConfig->set($guildID, "porn", array("allowedChannels" => $pornArray));
                $msg = "Porn has now been enabled on this channel, enjoy, you perv ;)";
                break;
            case "disablePorn":
                $pornArray = $this->serverConfig->getAll($guildID)->porn->allowedChannels;
                foreach ($pornArray as $key => $value) {
                    if ($value == $channelID) {
                        unset($pornArray[$key]);
                    }
                }

                $this->serverConfig->set($guildID, "porn", array("allowedChannels" => $pornArray));
                $msg = "Porn has now been disabled on this channel. :(";
                break;
            case "addKillmails":
                // %config addKillmails character characterID
                $typeName = trim($input[2]);
                $typeID = trim($input[3]);

                switch ($typeName) {
                    case "character":
                        // Check said char exists on the killboard..
                        $exists = json_decode($this->curl->get("https://evedata.xyz/api/character/information/{$typeID}/"));
                        if (isset($exists->characterID)) {
                            $this->db->execute("INSERT IGNORE INTO killmailPosting (channelID, typeName, typeID) VALUES (:channelID, :typeName, :typeID)", array(":channelID" => $channelID, ":typeName" => $typeName, ":typeID" => $typeID));
                            $msg = "**Success** killmails should start getting posted for {$exists->characterName} to this channel";
                        } else {
                            $msg = "**Error** characterID is not valid";
                        }
                        break;

                    case "corporation":
                        // Check said char exists on the killboard..
                        $exists = json_decode($this->curl->get("https://evedata.xyz/api/corporation/information/{$typeID}/"));
                        if (isset($exists->corporationID)) {
                            $this->db->execute("INSERT IGNORE INTO killmailPosting (channelID, typeName, typeID) VALUES (:channelID, :typeName, :typeID)", array(":channelID" => $channelID, ":typeName" => $typeName, ":typeID" => $typeID));
                            $msg = "**Success** killmails should start getting posted for {$exists->corporationName} to this channel";
                        } else {
                            $msg = "**Error** corporationID is not valid";
                        }

                        break;

                    case "alliance":
                        // Check said char exists on the killboard..
                        $exists = json_decode($this->curl->get("https://evedata.xyz/api/alliance/information/{$typeID}/"));
                        if (isset($exists->allianceID)) {
                            $this->db->execute("INSERT IGNORE INTO killmailPosting (channelID, typeName, typeID) VALUES (:channelID, :typeName, :typeID)", array(":channelID" => $channelID, ":typeName" => $typeName, ":typeID" => $typeID));
                            $msg = "**Success** killmails should start getting posted for {$exists->allianceName} to this channel";
                        } else {
                            $msg = "**Error** allianceID is not valid";
                        }
                        break;
                }
                break;
            case "removeKillmails":
                break;
            case "listKillmails":
                break;
            case "addTwitterOauth":
                // Add oauth settings for twitter, and send twitter messages to the channel it was enabled in, unless channelID was passed along
                break;
            case "removeTwitterOauth":
                // Disable twitter, and remove the oauth keys
                break;
            case "addSiphonKey":
                // Add an apikey used for checking for siphons, output to the channel it was enabled in, unless a channelID was passed along
                break;
            case "removeSiphonKey":
                break;
            case "addMailKey":
                // same as add siphon
                break;
            case "removeMailKey":
                break;
            case "addNotificationKey":
                // same as add siphon
                break;
            case "removeNotificationKey":
                break;
            case "addAuth":
                // Enable authentication for a characterID, corporationID or allianceID - have multiple, and let them map 1:1 to groups on Discord (if group doesn't exist, create it)
                break;
            case "removeAuth":
                break;
            case "addJabberReader":
                // Setup a socket to listen for messages, make them prepend a key for the channel it was enabled in (unless a channelID was specified)
                break;
            case "removeJabberReader":
                break;
            default:
                $msg = "Error, no configuration option picked. Available configuration options are: setTrigger, enablePorn, disablePorn, addTwitterOauth, removeTwitterOauth, addSiphonKey, removeSiphonKey, addMailKey, removeMailKey, addNotificationKey, removeNotificationKey, addJabberReader, removeJabberReader, addAuth, removeAuth";
                break;
        }

        $this->message->reply($msg);

        // Mark this as garbage
        $this->isGarbage();
    }
}