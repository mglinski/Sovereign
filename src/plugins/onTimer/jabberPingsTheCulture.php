<?php

namespace Sovereign\Plugins\onTimer;

use Discord\Discord;
use Discord\Parts\Channel\Channel;
use Monolog\Logger;
use Sovereign\Lib\Config;
use Sovereign\Lib\cURL;
use Sovereign\Lib\Db;
use Sovereign\Lib\Permissions;
use Sovereign\Lib\ServerConfig;
use Sovereign\Lib\Settings;
use Sovereign\Lib\Users;

class jabberPingsTheCulture extends \Threaded implements \Collectable
{
    /**
     * @var Discord
     */
    protected $discord;
    /**
     * @var Logger
     */
    protected $log;
    /**
     * @var Config
     */
    protected $config;
    /**
     * @var Db
     */
    protected $db;
    /**
     * @var cURL
     */
    protected $curl;
    /**
     * @var Settings
     */
    protected $settings;
    /**
     * @var Permissions
     */
    protected $permissions;
    /**
     * @var ServerConfig
     */
    protected $serverConfig;
    /**
     * @var Users
     */
    protected $users;
    /**
     * @var array
     */
    protected $extras;

    public function __construct($discord, $log, $config, $db, $curl, $settings, $permissions, $serverConfig, $users, $extras)
    {
        $this->discord = $discord;
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
        $handle = fopen("/tmp/discord.db", "r+");
        flock($handle, LOCK_EX);

        $message = "";
        while ($row = fgets($handle)) {
            if (!empty($row)) {
                $row = str_replace("\n", "", str_replace("\r", "", str_replace("^@", "", $row)));
                if ($row == "" || $row == " ") {
                    continue;
                }

                $message .= $row . " | ";
            }
        }

        if (!empty($message)) {
            // Strip out the last |
            $message = trim(substr($message, 0, -2));
            $channelID = 154221481625124864;
            /** @var Channel $channel */
            $channel = Channel::find($channelID);
            $this->log->addInfo("Sending ping to #pings on The Culture");
            $channel->sendMessage("@everyone " . $message);
        }

        flock($handle, LOCK_UN);
        fclose($handle);
        $handle = fopen("/tmp/discord.db", "w+");
        fclose($handle);
        chmod("/tmp/discord.db", 0777);
        $data = null;
        $handle = null;
        clearstatcache();
    }
}
