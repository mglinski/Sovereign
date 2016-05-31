<?php

namespace Sovereign\Plugins\onTimer;

use Discord\Discord;
use Monolog\Logger;
use Sovereign\Lib\Config;
use Sovereign\Lib\cURL;
use Sovereign\Lib\Db;
use Sovereign\Lib\Permissions;
use Sovereign\Lib\ServerConfig;
use Sovereign\Lib\Settings;
use Sovereign\Lib\Users;

class memory extends \Threaded implements \Collectable
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
        $this->log->addInfo("Memory in use before garbage collection: " . memory_get_usage() / 1024 / 1024 . "MB");
        gc_collect_cycles();
        $this->log->addInfo("Memory in use after garbage collection: " . memory_get_usage() / 1024 / 1024 . "MB");
    }
}
