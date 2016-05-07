<?php

namespace Sovereign\Plugins;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Sovereign\Sovereign;

class corp {
    public static function onMessage(Message $message, Discord $discord, $config, Sovereign $bot) {
        $container = $bot->getContainer();
        $explode = explode(" ", $message->content);
        $name = isset($explode[1]) ? $explode[1] : "";

        $url = "http://rena.karbowiak.dk/api/search/corporation/{$name}/";
        $data = @json_decode($container["curl"]->get($url), true)["corporation"];
        if(empty($data))
            return $message->reply("**Error:** no results was returned.");

        if(count($data) > 1) {
            $results = array();
            foreach($data as $corp)
                $results[] = $corp["corporationName"];
            return $message->reply("**Error:** more than one result was returned: " . implode(", ", $results));
        }

        // Get stats
        $corporationID = $data[0]["corporationID"];
        $statsURL = "https://beta.eve-kill.net/api/corpInfo/corporationID/" . urlencode($corporationID) ."/";
        $stats = json_decode($container["curl"]->get($statsURL), true);
        if(empty($stats))
            return $message->reply("**Error:** no data available");

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

        $message->reply($msg);
    }

    public function onStart() {

    }

    public function onTimer() {

    }

    public function information() {
        return (object) array(
            "description" => "Fetches data from EVE-KILL about a corporation",
            "usage" => "<corporationName>",
            "permission" => 1//1 is everyone, 2 is only admin
        );
    }
}