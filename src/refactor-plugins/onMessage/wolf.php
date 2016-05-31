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

class wolf extends \Threaded implements \Collectable
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
        $query = urlencode(implode(" ", $explode));
        $appID = urlencode($this->config->get("appID", "wolframalpha"));

        // WTB JSON endpoint...
        $data = json_decode(json_encode(new \SimpleXMLElement($this->curl->get("http://api.wolframalpha.com/v2/query?appid={$appID}&input={$query}"))), true);

        $result = $data["pod"][1]["subpod"];

        if (!empty($result)) {
            $image = $result["img"]["@attributes"]["src"];
            $text = $result["plaintext"];

            if (strlen($image) > 0) {
                $this->message->reply("{$text}\r\n {$image}");
                $wolfFileName = md5($query);
                file_put_contents(__DIR__ . "/../../../cache/image/{$wolfFileName}.gif", $image);
                $this->message->getChannelAttribute()->sendFile(__DIR__ . "/../../../cache/image/{$wolfFileName}.gif", "{$wolfFileName}.gif");
            } else {
                $this->message->reply("Result: {$image}");
            }
        } else {
            $this->message->reply("WolframAlpha did not have an answer to your query..");
        }

        // Mark this as garbage
        $this->isGarbage();
    }
}