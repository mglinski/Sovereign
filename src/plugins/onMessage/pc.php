<?php

namespace Sovereign\Plugins;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Sovereign\Sovereign;
use SimpleXMLElement;

class pc {
    public static function onMessage(Message $message, Discord $discord, $config, Sovereign $bot) {
        $container = $bot->getContainer();
        $explode = explode(" ", $message->content);
        $system = isset($explode[0]) ? $explode[0] == "%pc" ? "global" : str_replace("%", "", $explode[0]) : "global";
        $item = isset($explode[1]) ? $explode[1] : "";

        // Stuff that doesn't need a db lookup
        $quickLookUps = [
            "plex" => array("typeName" => "30 Day Pilot's License Extension (PLEX)", "typeID" => 29668),
            "injector" => array("typeName" => "Skill Injector", "typeID" => 40520),
            "extractor" => array("typeName" => "Skill Extractor", "typeID" => 40519)
        ];

        if($system && $item) {
            if(isset($quickLookUps[$item])) {
                $single = $quickLookUps[$item];
                $multiple = null;
            } else {
                $single = $container["db"]->queryRow("SELECT typeID, typeName FROM invTypes WHERE typeName = :item", array(":item" => $item));
                $multiple = $container["db"]->query("SELECT typeID, typeName FROM invTypes WHERE typeName LIKE :item LIMIT 5", array(":item" => $item));
            }

            if(count($multiple) == 1)
                $single = $multiple[0];

            if(empty($single) && !empty($multiple)) {
                $items = array();
                foreach ($multiple as $item)
                    $items[] = $item["typeName"];
                $items = implode(", ", $items);
                return $message->reply("**Multiple results found:** {$items}");
            }

            // If there is a single result, we'll get data now!
            if ($single) {
                $typeID = $single["typeID"];
                $typeName = $single["typeName"];

                if($system == "global") {
                    $system = "global";
                    $data = new SimpleXMLElement($container["curl"]->get("https://api.eve-central.com/api/marketstat?typeid={$typeID}"));
                }
                else {
                    $solarSystemID = $container["db"]->queryField("SELECT solarSystemID FROM mapSolarSystems WHERE solarSystemName = :system", "solarSystemID", array(":system" => $system));
                    $data = new SimpleXMLElement($container["curl"]->get("https://api.eve-central.com/api/marketstat?usesystem={$solarSystemID}&typeid={$typeID}"));
                }
                $lowBuy = number_format((float)$data->marketstat->type->buy->min, 2);
                $avgBuy = number_format((float)$data->marketstat->type->buy->avg, 2);
                $highBuy = number_format((float)$data->marketstat->type->buy->max, 2);
                $lowSell = number_format((float)$data->marketstat->type->sell->min, 2);
                $avgSell = number_format((float)$data->marketstat->type->sell->avg, 2);
                $highSell = number_format((float)$data->marketstat->type->sell->max, 2);
                $solarSystemName = $system == "pc" ? "Global" : ucfirst($system);
                $messageData = "```
typeName: {$typeName}
solarSystemName: {$solarSystemName}
Buy:
  Low: {$lowBuy}
  Avg: {$avgBuy}
  High: {$highBuy}
Sell:
  Low: {$lowSell}
  Avg: {$avgSell}
  High: {$highSell}```";
                $message->reply($messageData);
            } else {
                $message->reply("**Error:** ***{$item}*** not found");
            }
        } else {
            $message->reply("**Error:** No itemName set..");
        }
    }

    public function onStart() {

    }

    public function onTimer() {

    }

    public function information() {
        return (object) array(
            "description" => "Lets you check prices of items in EVE. Available systems: pc (global), Jita, Amarr, Dodixie, Rens and Hek",
            "usage" => "<itemName>",
            "permission" => 1,//1 is everyone, 2 is only admin
            "commands" => json_decode('["Jita","Amarr","Dodixie","Rens","Hek"]')
        );
    }
}