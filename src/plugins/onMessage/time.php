<?php

namespace Sovereign\Plugins;

use DateTime;
use DateTimeZone;
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

class time extends \Threaded implements \Collectable
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
        $date = date("d-m-Y");
        $fullDate = date("Y-m-d H:i:s");
        $dateTime = new DateTime($fullDate);
        $et = $dateTime->setTimezone(new DateTimeZone("America/New_York"));
        $et = $et->format("H:i:s");
        $pt = $dateTime->setTimezone(new DateTimeZone("America/Los_Angeles"));
        $pt = $pt->format("H:i:s");
        $utc = $dateTime->setTimezone(new DateTimeZone("UTC"));
        $utc = $utc->format("H:i:s");
        $cet = $dateTime->setTimezone(new DateTimeZone("Europe/Copenhagen"));
        $cet = $cet->format("H:i:s");
        $msk = $dateTime->setTimezone(new DateTimeZone("Europe/Moscow"));
        $msk = $msk->format("H:i:s");
        $aest = $dateTime->setTimezone(new DateTimeZone("Australia/Sydney"));
        $aest = $aest->format("H:i:s");
        $msg = "**Current EVE Time:** {$utc} / **EVE Date:** {$date} / **PT:** {$pt} / **ET:** {$et} / **CET:** {$cet} / **MSK:** {$msk} / **AEST:** {$aest}";
        $this->message->reply($msg);
    }

    public function information()
    {
        return (object)array(
            "description" => "Tells you the current EVE Time and time in various other timezones",
            "usage" => "",
            "permission" => 1//1 is everyone, 2 is only admin
        );
    }
}