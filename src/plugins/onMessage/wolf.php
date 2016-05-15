<?php

namespace Sovereign\Plugins;

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
     * @var \WolframAlpha\Engine
     */
    private $wolframAlpha;
    /**
     * @var int
     */
    private $startTime;

    public function __construct($message, $discord, $log, $config, $db, $curl, $settings, $permissions, $serverConfig, $users, $wolframAlpha, $startTime)
    {
        $this->message = $message;
        $this->discord = $discord;
        $this->log = $log;
        $this->config = $config;
        $this->db = $db;
        $this->curl = $curl;
        $this->settings = $settings;
        $this->permissions = $permissions;
        $this->serverConfig = $serverConfig;
        $this->users = $users;
        $this->wolframAlpha = $wolframAlpha;
        $this->startTime = $startTime;
    }

    public function run()
    {
        $explode = explode(" ", $this->message->content);
        unset($explode[0]);
        $query = implode(" ", $explode);

        /** @var \WolframAlpha\QueryResult $result */
        $result = $this->wolframAlpha->process($query, array(), array("image", "plaintext"));
        /** @var \WolframAlpha\Pod $pod */
        $pod = $result->pods["Result"];

        if (!empty($pod)) {
            /** @var \WolframAlpha\Subpod $subPod */
            $subPod = $pod->subpods[0];

            if (strlen($subPod->img->src) > 0)
                $this->message->reply("Result: {$subPod->plaintext}\r\n {$subPod->img->src}");
            else
                $this->message->reply("Result: {$subPod->plaintext}");
        } else {
            $this->message->reply("WolframAlpha did not have an answer to your query..");
        }

    }
}