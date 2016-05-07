<?php
namespace Sovereign;


use Discord\Cache\Cache;
use Discord\Cache\Drivers\ArrayCacheDriver;
use Discord\Cache\Drivers\RedisCacheDriver;
use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\Parts\User\User;
use Discord\Parts\WebSockets\PresenceUpdate;
use Discord\WebSockets\Event;
use Discord\WebSockets\WebSocket;
use Monolog\Logger;
use Pimple\Container;
use Sovereign\Lib\Config;
use Sovereign\Lib\cURL;
use Sovereign\Lib\Db;
use Sovereign\Lib\Permissions;
use Sovereign\Lib\Settings;
use Sovereign\Lib\Users;

/**
 * Class Sovereign
 * @package Sovereign
 */
class Sovereign {
    /**
     * @var WebSocket
     */
    public $websocket;
    /**
     * @var Discord
     */
    protected $discord;
    /**
     * @var
     */
    public $voice;
    /**
     * @var Container
     */
    protected $container;
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
     * @var Users
     */
    protected $users;
    /**
     * @var array
     */
    private $onMessage = [];
    /**
     * @var array
     */
    private $onStart = [];
    /**
     * @var array
     */
    private $onTimer = [];

    /**
     * Sovereign constructor.
     * @param Container $container
     */
    public function __construct(Container $container) {
        $this->container = $container;
        $this->log = $container["log"];
        $this->config = $container["config"];
        $this->db = $container["db"];
        $this->curl = $container["curl"];
        $this->settings = $container["settings"];
        $this->permissions = $container["permissions"];
        $this->users = $container["users"];

        // Load the plugins (It populates $onMessage, $onStart, $onTimer)
        $this->loadPlugins();

        // Fire up the onStart plugins
        
        // Init Discord and Websocket
        $this->log->addInfo("Initializing Discord and Websocket connections..");
        $this->discord = Discord::createWithBotToken($this->config->get("token", "bot"));
        Cache::setCache(new RedisCacheDriver("127.0.0.1", 6379, null, 1));
        $this->websocket = new WebSocket($this->discord);
    }

    public function run() {
        $this->websocket->on("ready", function(Discord $discord) {
            $this->log->addInfo("Websocket connected..");
            
            // Update our presence status
            $discord->updatePresence($this->websocket, $this->config->get("presense", "bot", "table flippin'"), false);

            // Setup the timers for the timer plugins
            $config = $this->db->query("SELECT settings FROM settings");
            $that = $this;
            foreach($this->getOnTimerPlugins() as $command => $data) {
                $this->websocket->loop->addPeriodicTimer($data["timer"], function() use ($data, $discord, $that, $config) {
                    $data["class"]::onTimer($discord, $this, $config);
                });
            }
            
            // Foreach guild we'll setup a new cleverbot instance, if one doesn't already exist.
            foreach($discord->guilds->all() as $guild) {
                $serverID = $guild->id;
                $botNick = $this->db->queryField("SELECT nick FROM cleverbot WHERE serverID = :serverID", "nick", [":serverID" => $serverID]);
                if(!$botNick) {
                    $result = $this->curl->post("https://cleverbot.io/1.0/create", ["user" => $this->config->get("user", "cleverbot"), "key" => $this->config->get("key", "cleverbot")]);
                    if($result) {
                        $result = @json_decode($result);
                        $nick = isset($result->nick) ? $result->nick : false;

                        if($nick)
                            $this->db->execute("INSERT IGNORE INTO cleverbot (serverID, nick) VALUES (:serverID, :nick)", [":serverID" => $serverID, ":nick" => $nick]);
                    }
                }
            }
        });

        $this->websocket->on("error", function($error, $websocket) {
            $this->log->addError("An error occured on the websocket", [$error->getMessage()]);
        });

        $this->websocket->on("close", function($opCode, $reason) {
            $this->log->addWarning("Websocket got closed", ["code" => $opCode, "reason" => $reason]);
        });

        $this->websocket->on("reconnecting", function() {
            $this->log->addInfo("Websocket is reconnecting..");
        });
        
        $this->websocket->on("reconnected", function() {
            $this->log->addInfo("Websocket was reconnected..");
        });

        // Handle incoming message logging
        $this->websocket->on(Event::MESSAGE_CREATE, function(Message $message, Discord $discord) {
            // Ignore if it's from ourselves
            if($message->author->id != $discord->user->id) {
                $this->log->addNotice("Message from {$message->author->username}", [$message->content]);
                $this->users->set($message->author->id, $message->author->username, "online", "", date("Y-m-d H:i:s"), date("Y-m-d H:i:s"), $message->content);
            }
        });

        // Handle plugin running
        $this->websocket->on(Event::MESSAGE_CREATE, function(Message $message, Discord $discord) {
            $guildID = $message->full_channel->guild->id;

            // Get server config
            $config = $this->settings->get($guildID);

            // Define the prefix if it isn't already set..
            $config->prefix = isset($config->prefix) ? $config->prefix : $this->config->get("prefix", "bot");

            // Check if the user requested an onMessage plugin
            if(substr($message->content, 0, strlen($config->prefix)) == $config->prefix) {
                foreach($this->onMessage as $command => $data) {
                    $parts = [];
                    $content = explode(" ", $message->content);

                    foreach($content as $index => $c) {
                        foreach(explode("\n", $c) as $p) {
                            $parts[] = $p;
                        }
                    }

                    if($parts[0] == $config->prefix . $command) {
                        $userPerms = $this->permissions->get($message->author->id, $guildID) ? $this->permissions->get($message->author->id, $guildID) : 1;

                        if($userPerms >= $data["permissions"]) {
                            try {
                                $data["class"]::onMessage($message, $discord, $config, $this);
                                $this->log->addInfo("{$message->author->username}#{$message->author->discriminator} ({$message->author}) ran command {$config->prefix}{$command}", $content);
                            } catch (\Throwable $e) {
                                try {
                                    $this->log->addError("Error running the command {$config['prefix']}{$command}", ['message' => $e->getMessage()]);
                                    $message->reply("There was an error running the command. `{$e->getMessage()}`");
                                } catch(\Throwable $e2){}
                            }
                        } else {
                            try {
                                $message->reply("Sorry, you do not have permissions to execute this command..");
                            } catch (\Throwable $e) {}
                            $this->log->addWarning("{$message->author->username}#{$message->author->discriminator} ({$message->author}) attempted to run command {$config->prefix}{$command}", $content);
                        }
                    }
                }
            }
        });

        // Handle if it's a message for the bot (Cleverbot invocation)
        $this->websocket->on(Event::MESSAGE_CREATE, function(Message $message, Discord $discord) {
            $guildID = $message->full_channel->guild->id;
            $cleverBotNick = $this->db->queryField("SELECT nick FROM cleverbot WHERE serverID = :serverID", "nick", [":serverID" => $guildID]);
            
            // If we got highlighted we should probably answer back
            if(stristr($message->content, $discord->user->id)) {
                $msg = str_replace("<@{$discord->user->id}>", $discord->user->username, $message->content);
                $response = $this->curl->post("https://cleverbot.io/1.0/ask", ["user" => $this->config->get("user", "cleverbot"), "key" => $this->config->get("key", "cleverbot"), "nick" => $cleverBotNick, "text" =>$msg]);
                if($response) {
                    $resp = @json_decode($response);
                    $reply = isset($resp->response) ? $resp->response : false;
                    if($reply)
                        $message->reply($reply);
                }
            } 
        });

        // Handle presence updates
        $this->websocket->on(Event::PRESENCE_UPDATE, function(PresenceUpdate $presenceUpdate) {
            if($presenceUpdate->user->id && $presenceUpdate->user->username) {
                $this->log->addInfo("Updating presence info for {$presenceUpdate->user->username}");
                $this->users->set($presenceUpdate->user->id, $presenceUpdate->user->username, $presenceUpdate->status, $presenceUpdate->game, date("Y-m-d H:i:s"), null, null);
            }
        });

        // Handle guild updates (Create a new cleverbot \nick\ for this new guild)
        $this->websocket->on(Event::GUILD_UPDATE, function(Message $message, Discord $discord) {
            $this->log->addNotice("Guild update?");
            var_dump($message);
            var_dump($discord);
        });

        // Run the websocket, and in turn, the bot!
        $this->websocket->run();
    }

    public function getOnMessagePlugins() {
        return $this->onMessage;
    }

    public function getOnStartPlugins() {
        return $this->onStart;
    }

    public function getOnTimerPlugins() {
        return $this->onTimer;
    }

    public function getContainer() {
        return $this->container;
    }
    
    private function addPlugin($type, $command, $class, $perms, $description, $usage, $timer) {
        $this->$type[$command] = [
            "permissions" => $perms,
            "class" => $class,
            "description" => $description,
            "usage" => $usage,
            "timer" => $timer
        ];
    }

    private function loadPlugins() {
        $pluginTypes = ["onMessage", "onStart", "onTimer"];
        $pluginDir = __DIR__ . "/plugins/";

        foreach($pluginTypes as $type) {
            $files = glob($pluginDir . $type ."/*.php");
            foreach($files as $file) {
                $filename = basename($file);
                $basename = str_replace(".php", "", $filename);
                $className = "\\Sovereign\\Plugins\\{$basename}";

                $load = new $className();
                $information = $load->information();
                $commands = isset($information->commands) ? $information->commands : null;
                $description = $information->description;
                $usage = $information->usage;
                $permission = $information->permission;
                $timer = isset($information->timer) ? $information->timer : null;
                
                if($commands) {
                    foreach ($commands as $command) {
                        $this->addPlugin($type, strtolower($command), $className, $permission, $description, $usage, $timer);
                    }
                }
                $this->addPlugin($type, $basename, $className, $permission, $description, $usage, $timer);
            }
        }
    }
}