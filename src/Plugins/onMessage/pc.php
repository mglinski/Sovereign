<?php

namespace Sovereign\Plugins\onMessage;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Monolog\Logger;
use SimpleXMLElement;
use Sovereign\Lib\Config;
use Sovereign\Lib\cURL;
use Sovereign\Lib\Db;
use Sovereign\Lib\Permissions;
use Sovereign\Lib\ServerConfig;
use Sovereign\Lib\Settings;
use Sovereign\Lib\Users;

class pc extends \Threaded implements \Collectable
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
        $prefix = $this->channelConfig->prefix;
        $system = isset($explode[0]) ? $explode[0] == "{$prefix}pc" ? "global" : str_replace($prefix, "", $explode[0]) : "global";
        unset($explode[0]);
        $item = implode(" ", $explode);

        // Stuff that doesn't need a db lookup
        $quickLookUps = [
            "plex" => array("typeName" => "30 Day Pilot's License Extension (PLEX)", "typeID" => 29668),
            "injector" => array("typeName" => "Skill Injector", "typeID" => 40520),
            "extractor" => array("typeName" => "Skill Extractor", "typeID" => 40519)
        ];

        if ($system && $item) {
            if (isset($quickLookUps[$item])) {
                $single = $quickLookUps[$item];
                $multiple = null;
            } else {
                $single = $this->db->queryRow("SELECT typeID, typeName FROM invTypes WHERE typeName = :item", array(":item" => $item));
                $multiple = $this->db->query("SELECT typeID, typeName FROM invTypes WHERE typeName LIKE :item LIMIT 5", array(":item" => $item));
            }

            if (count($multiple) == 1) {
                $single = $multiple[0];
            }

            if (empty($single) && !empty($multiple)) {
                $items = array();
                foreach ($multiple as $item) {
                    $items[] = $item["typeName"];
                }
                $items = implode(", ", $items);
                return $this->message->reply("**Multiple results found:** {$items}");
            }

            // If there is a single result, we'll get data now!
            if ($single) {
                $typeID = $single["typeID"];
                $typeName = $single["typeName"];

                if ($system == "global") {
                    $system = "global";
                    $data = new SimpleXMLElement($this->curl->get("https://api.eve-central.com/api/marketstat?typeid={$typeID}"));
                } else {
                    $solarSystemID = $this->db->queryField("SELECT solarSystemID FROM mapSolarSystems WHERE solarSystemName = :system", "solarSystemID", array(":system" => $system));
                    $data = new SimpleXMLElement($this->curl->get("https://api.eve-central.com/api/marketstat?usesystem={$solarSystemID}&typeid={$typeID}"));
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
                $this->message->reply($messageData);
            } else {
                $this->message->reply("**Error:** ***{$item}*** not found");
            }
        } else {
            $this->message->reply("**Error:** No itemName set..");
        }

        // Mark this as garbage
        $this->isGarbage();
    }
}