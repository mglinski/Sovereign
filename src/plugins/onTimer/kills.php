<?php

namespace Sovereign\Plugins\onTimer;

use Discord\Discord;
use Discord\Parts\Channel\Channel;
use Monolog\Logger;
use Sovereign\Lib\Config;
use Sovereign\Lib\cURL;
use Sovereign\Lib\Db;
use Sovereign\Lib\Permissions;
use Sovereign\Lib\ServerConfig;
use Sovereign\Lib\Settings;
use Sovereign\Lib\Users;

class kills extends \Threaded implements \Collectable {
    /**
     * @var Discord
     */
    protected $discord;
    /**
     * @var Logger
     */
    protected $log;
    /**
     * @var Config
     */
    protected $config;
    /**
     * @var Db
     */
    protected $db;
    /**
     * @var cURL
     */
    protected $curl;
    /**
     * @var Settings
     */
    protected $settings;
    /**
     * @var Permissions
     */
    protected $permissions;
    /**
     * @var ServerConfig
     */
    protected $serverConfig;
    /**
     * @var Users
     */
    protected $users;
    /**
     * @var array
     */
    protected $extras;

    public function __construct($discord, $log, $config, $db, $curl, $settings, $permissions, $serverConfig, $users, $extras) {
        $this->discord = $discord;
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

    public function run() {
        $channels = $this->db->query("SELECT * FROM killmailPosting");

        foreach($channels as $channel) {
            $rowID = $channel["id"];
            $type = $channel["typeName"];
            $id = $channel["typeID"];
            $latest = $channel["latestKillID"];
            $channelID = $channel["channelID"];

            // Get the killdata
            $killData = json_decode($this->curl->get("https://evedata.xyz/api/killlist/latest/"));

            if(!empty($killData)) {
                foreach ($killData as $kill) {
                    if ($kill->killID) { // > $latest) {
                        switch ($type) {
                            case "character":
                                if ($kill->victim->characterID == $id) {
                                    $msg = "{$kill->victim->characterName} ({$kill->victim->corporationName} / {$kill->victim->allianceName}) lost {$kill->victim->shipTypeName} in {$kill->solarSystemName} ({$kill->regionName}) with a total value of {$kill->totalValue}isk | https://beta.eve-kill.net/kill/{$kill->killID}/";
                                }

                                foreach ($kill->attackers as $attacker) {
                                    if ($attacker->characterID == $id && $attacker->finalBlow == 1) {
                                        $msg = "{$attacker->characterName} participated in killing {$kill->victim->characterName} ({$kill->victim->corporationName} / {$kill->victim->allianceName} / {$kill->victim->shipTypeName}) in a {$attacker->shipTypeName} doing a total of {$attacker->damageDone} damage, and helped destroy {$kill->totalValue}isk | https://beta.eve-kill.net/kill/{$kill->killID}/";
                                    }
                                }
                                break;
                            case "corporation":
                                if ($kill->victim->corporationID == $id) {
                                    $msg = "{$kill->victim->characterName} ({$kill->victim->corporationName} / {$kill->victim->allianceName}) lost {$kill->victim->shipTypeName} in {$kill->solarSystemName} ({$kill->regionName}) with a total value of {$kill->totalValue}isk";
                                }

                                foreach ($kill->attackers as $attacker) {
                                    if ($attacker->corporationID == $id && $attacker->finalBlow == 1) {
                                        $msg = "{$attacker->characterName} participated in killing {$kill->victim->characterName} ({$kill->victim->corporationName} / {$kill->victim->allianceName} / {$kill->victim->shipTypeName}) in a {$attacker->shipTypeName} doing a total of {$attacker->damageDone} damage, and helped destroy {$kill->totalValue}isk | https://beta.eve-kill.net/kill/{$kill->killID}/";
                                    }
                                }
                                break;
                            case "alliance":
                                if ($kill->victim->allianceID == $id) {
                                    $msg = "{$kill->victim->characterName} ({$kill->victim->corporationName} / {$kill->victim->allianceName}) lost {$kill->victim->shipTypeName} in {$kill->solarSystemName} ({$kill->regionName}) with a total value of {$kill->totalValue}isk";
                                }

                                foreach ($kill->attackers as $attacker) {
                                    if ($attacker->allianceID == $id && $attacker->finalBlow == 1) {
                                        $msg = "{$attacker->characterName} participated in killing {$kill->victim->characterName} ({$kill->victim->corporationName} / {$kill->victim->allianceName} / {$kill->victim->shipTypeName}) in a {$attacker->shipTypeName} doing a total of {$attacker->damageDone} damage, and helped destroy {$kill->totalValue}isk | https://beta.eve-kill.net/kill/{$kill->killID}/";
                                    }
                                }
                                break;
                        }
                    }
                    $this->db->execute("UPDATE killmailPosting SET latestKillID = :killID WHERE id = :rowID", array(":killID" => $kill->killID, ":rowID" => $rowID));
                }

                if(!empty($msg)) {
                    $channel = Channel::find($channelID);
                    $channel->sendMessage($msg);
                }
            }
        }
    }
}
