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

class corp extends \Threaded implements \Collectable
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

        $url = "https://evedata.xyz/api/search/corporation/" . urlencode($name) . "/";
        $data = @json_decode($this->curl->get($url), true)["corporation"];
        if (empty($data)) {
            return $this->message->reply("**Error:** no results was returned.");
        }

        if (count($data) > 1) {
            $results = array();
            foreach ($data as $corp) {
                $results[] = $corp["corporationName"];
            }
            return $this->message->reply("**Error:** more than one result was returned: " . implode(", ", $results));
        }

        // Get stats
        $corporationID = $data[0]["corporationID"];
        $statsURL = "https://beta.eve-kill.net/api/corpInfo/corporationID/" . urlencode($corporationID) . "/";
        $stats = json_decode($this->curl->get($statsURL), true);
        if (empty($stats)) {
            return $this->message->reply("**Error:** no data available");
        }

        $corporationName = @$stats["corporationName"];
        $allianceName = isset($stats["allianceName"]) ? $stats["allianceName"] : "None";
        $factionName = isset($stats["factionName"]) ? $stats["factionName"] : "None";
        $ceoName = @$stats["ceoName"];
        $homeStation = @$stats["stationName"];
        $taxRate = @$stats["taxRate"];
        $corporationActiveArea = @$stats["corporationActiveArea"];
        $allianceActiveArea = @$stats["allianceActiveArea"];
        $lifeTimeKills = @$stats["lifeTimeKills"];
        $lifeTimeLosses = @$stats["lifeTimeLosses"];
        $memberCount = @$stats["memberArrayCount"];
        $superCaps = @count($stats["superCaps"]);
        $ePeenSize = @$stats["ePeenSize"];
        $url = "https://beta.eve-kill.net/corporation/" . @$stats["corporationID"] . "/";
        $msg = "```corporationName: {$corporationName}
allianceName: {$allianceName}
factionName: {$factionName}
ceoName: {$ceoName}
homeStation: {$homeStation}
taxRate: {$taxRate}
corporationActiveArea: {$corporationActiveArea}
allianceActiveArea: {$allianceActiveArea}
lifeTimeKills: {$lifeTimeKills}
lifeTimeLosses: {$lifeTimeLosses}
memberCount: {$memberCount}
superCaps: {$superCaps}
ePeenSize: {$ePeenSize}
```
For more info, visit: $url";

        $this->message->reply($msg);

        // Mark this as garbage
        $this->isGarbage();
    }
}