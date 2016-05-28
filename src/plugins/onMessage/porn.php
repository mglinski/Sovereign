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
            $type = isset($explode[1]) ? $explode[1] : "";
            $urls = [];
            switch ($type) {
                case "redheads":
                case "redhead":
                case "red":
                    $urls = array(
                        "https://api.imgur.com/3/gallery/r/redheads/time/all/",
                        "https://api.imgur.com/3/gallery/r/ginger/time/all/",
                        "https://api.imgur.com/3/gallery/r/FireCrotch/time/all/"
                    );
                    break;
                case "blondes":
                    $urls = array(
                        "https://api.imgur.com/3/gallery/r/blondes/time/all/"
                    );
                    break;
                case "asians":
                    $urls = array(
                        "https://api.imgur.com/3/gallery/r/AsiansGoneWild/time/all/"
                    );
                    break;
                case "gonewild":
                    $urls = array(
                        "https://api.imgur.com/3/gallery/r/gonewild/time/all/"
                    );
                    break;
                case "realgirls":
                    $urls = array(
                        "https://api.imgur.com/3/gallery/r/realgirls/time/all/"
                    );
                    break;
                case "palegirls":
                    $urls = array(
                        "https://api.imgur.com/3/gallery/r/palegirls/time/all/"
                    );
                    break;
                case "gif":
                    $urls = array(
                        "https://api.imgur.com/3/gallery/r/NSFW_GIF/time/all/"
                    );
                    break;
                case "lesbians":
                    $urls = array(
                        "https://api.imgur.com/3/gallery/r/lesbians/time/all/"
                    );
                    break;
                case "tattoos":
                    $urls = array(
                        "https://api.imgur.com/3/gallery/r/Hotchickswithtattoos/time/all/"
                    );
                    break;
                case "mgw":
                case "militarygonewild":
                    $urls = array(
                        "https://api.imgur.com/3/gallery/r/MilitaryGoneWild/time/all/"
                    );
                    break;
                case "amateur":
                    $urls = array(
                        "https://api.imgur.com/3/gallery/r/AmateurArchives/time/all/"
                    );
                    break;
                case "college":
                    $urls = array(
                        "https://api.imgur.com/3/gallery/r/collegesluts/time/all/"
                    );
                    break;
                case "bondage":
                    $urls = array(
                        "https://api.imgur.com/3/gallery/r/bondage/time/all/"
                    );
                    break;
                case "milf":
                    $urls = array(
                        "https://api.imgur.com/3/gallery/r/milf/time/all/"
                    );
                    break;
                case "freckles":
                    $urls = array(
                        "https://api.imgur.com/3/gallery/r/FreckledGirls/time/all/"
                    );
                    break;
                case "cosplay":
                    $urls = array("https://api.imgur.com/3/gallery/r/cosplay/time/all/");
                    break;
                case "tits":
                case "breasts":
                case "boobs":
                    $urls = array("https://api.imgur.com/3/gallery/r/boobs/time/all/");
                    break;
                case "ass":
                    $urls = array("https://api.imgur.com/3/gallery/r/ass/time/all/");
                    break;
                case "food":
                    $urls = array("https://api.imgur.com/3/gallery/r/foodporn/time/all/");
                    break;
                case "gifrecipes":
                    $urls = array("https://api.imgur.com/3/gallery/r/gifrecipes/time/all/");
                    break;
                case "bbw":
                    $urls = array("https://api.imgur.com/3/gallery/r/bbw/time/all/");
                    break;
                case "cheese":
                case "dick":
                case "dong":
                case "penis":
                    $urls = array("https://api.imgur.com/3/gallery/r/penis/time/all/");
                    break;
                default:
                    $msg = "No endpoint selected. Currently available are: redheads, blondes, asians, gonewild, realgirls, palegirls, gif, lesbians, tattoos, mgw/militarygonewild, amateur, college, bondage, milf, freckles, boobs, ass, dong, bbw, food, gifrecipes and cosplay";
                    $this->message->reply($msg);
                    break;
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