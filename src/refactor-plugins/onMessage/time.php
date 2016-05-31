<?php

namespace Sovereign\Plugins\onMessage;

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
        $msg = "**Current EVE Time:** {$utc} / **EVE Date:** {$date} / **Los Angeles (PT):** {$pt} / **New York (ET):** {$et} / **Berlin/Copenhagen (CET):** {$cet} / **Moscow (MSK):** {$msk} / **Sydney (AEST):** {$aest}";
        $this->message->reply($msg);

        // Mark this as garbage
        $this->isGarbage();
    }
}