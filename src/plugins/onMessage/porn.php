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

class porn extends \Threaded implements \Collectable
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
        $pornConfig = @$this->channelConfig->porn;

        // This is one of those plugins that need to be allowed before it works
        if (isset($pornConfig->allowedChannels) && in_array($this->message->channel_id, $pornConfig->allowedChannels)) {
            $explode = explode(" ", $this->message->content);
            unset($explode[0]);
            $type = implode(" ", $explode);
            $categoryNames = array();
            $url = "";

            $categories = array(
                "redheads" => array(
                    "https://api.imgur.com/3/gallery/r/redheads/time/all/",
                    "https://api.imgur.com/3/gallery/r/ginger/time/all/",
                    "https://api.imgur.com/3/gallery/r/FireCrotch/time/all/"
                ),
                "blondes" => "https://api.imgur.com/3/gallery/r/blondes/time/all/",
                "asians" => "https://api.imgur.com/3/gallery/r/AsiansGoneWild/time/all/",
                "gonewild" => "https://api.imgur.com/3/gallery/r/gonewild/time/all/",
                "realgirls" => "https://api.imgur.com/3/gallery/r/realgirls/time/all/",
                "palegirls" => "https://api.imgur.com/3/gallery/r/palegirls/time/all/",
                "gif" => "https://api.imgur.com/3/gallery/r/NSFW_GIF/time/all/",
                "lesbians" => "https://api.imgur.com/3/gallery/r/lesbians/time/all/",
                "tattiis" => "https://api.imgur.com/3/gallery/r/Hotchickswithtattoos/time/all/",
                "mgw" => "https://api.imgur.com/3/gallery/r/MilitaryGoneWild/time/all/",
                "amateur" => "https://api.imgur.com/3/gallery/r/AmateurArchives/time/all/",
                "college" => "https://api.imgur.com/3/gallery/r/collegesluts/time/all/",
                "bondage" => "https://api.imgur.com/3/gallery/r/bondage/time/all/",
                "milf" => "https://api.imgur.com/3/gallery/r/milf/time/all/",
                "freckles" => "https://api.imgur.com/3/gallery/r/FreckledGirls/time/all/",
                "cosplay" => "https://api.imgur.com/3/gallery/r/cosplay/time/all/",
                "tits" => "https://api.imgur.com/3/gallery/r/boobs/time/all/",
                "ass" => "https://api.imgur.com/3/gallery/r/ass/time/all/",
                "food" => "https://api.imgur.com/3/gallery/r/foodporn/time/all/",
                "gifrecipes" => "https://api.imgur.com/3/gallery/r/gifrecipes/time/all/",
                "bbw" => "https://api.imgur.com/3/gallery/r/bbw/time/all/",
                "dongs" => "https://api.imgur.com/3/gallery/r/penis/time/all/",
            );

            foreach($categories as $catName => $catURL) {
                $categoryNames[] = $catName;
                if(strtolower($type) == strtolower($catName)) {
                    if(is_array($catURL))
                        $url = $catURL[array_rand($catURL)];
                    else
                        $url = $catURL;
                }
            }

            if(!$url) {
                $msg = "No endpoint selected. Currently available are: " . implode(", ", $categoryNames);
                $this->message->reply($msg);
            }

            if (!empty($urls)) {
                // Select a random url
                $url = $urls[array_rand($urls)];
                $clientID = $this->config->get("clientID", "imgur");
                $headers = array();
                $headers[] = "Content-type: application/json";
                $headers[] = "Authorization: Client-ID {$clientID}";
                $data = $this->curl->get($url, $headers);

                if ($data) {
                    $json = json_decode($data, true)["data"];
                    $img = $json[array_rand($json)];
                    $imageURL = $img["link"]; // gifv doesn't embed properly in discord, yet..
                    $msg = "**Title:** {$img["title"]} | **Section:** {$img["section"]} | **url:** {$imageURL}";
                    $this->message->reply($msg);
                }
            }

        } else {
            $this->message->reply("Sorry, this plugin is not allowed in this channel, speak to your admin to get it allowed (To enable, use {$this->channelConfig->prefix}config enablePorn)");
        }

        // Mark this as garbage
        $this->isGarbage();
    }
}