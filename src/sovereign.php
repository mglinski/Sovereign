<?php
namespace Sovereign;


use Discord\Cache\Cache;
use Discord\Cache\Drivers\ArrayCacheDriver;
use Discord\Cache\Drivers\RedisCacheDriver;
use Discord\Discord;
use Discord\Helpers\Guzzle;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\Guild;
use Discord\Parts\User\Game;
use Discord\Parts\WebSockets\PresenceUpdate;
use Discord\WebSockets\Event;
use Discord\WebSockets\WebSocket;
use Monolog\Logger;
use Pimple\Container;
use Sovereign\Lib\Config;
use Sovereign\Lib\cURL;
use Sovereign\Lib\Db;
use Sovereign\Lib\Permissions;
use Sovereign\Lib\ServerConfig;
use Sovereign\Lib\Settings;
use Sovereign\Lib\Users;
use SuperClosure\Serializer;

/**
 * Class Sovereign
 * @package Sovereign
 */
class Sovereign
{
    /**
     * @var WebSocket
     */
    public $websocket;
    /**
     * @var
     */
    public $voice;
    /**
     * @var Discord
     */
    protected $discord;
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
     * @var \Pool
     */
    private $pool;
    /**
     * @var int
     */
    private $startTime;

    /**
     * Sovereign constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->log = $container["log"];
        $this->config = $container["config"];
        $this->db = $container["db"];
        $this->curl = $container["curl"];
        $this->settings = $container["settings"];
        $this->permissions = $container["permissions"];
        $this->users = $container["users"];
        $this->startTime = time();
        $this->pool = new \Pool(15);

        // Fire up the onStart plugins

        // Init Discord and Websocket
        $this->log->addInfo("Initializing Discord and Websocket connections..");
        $this->discord = Discord::createWithBotToken($this->config->get("token", "bot"));
        // Use redis for caching data..
        //Cache::setCache(new RedisCacheDriver("127.0.0.1", 6379, null, 1));
        Cache::setCache(new ArrayCacheDriver());
        // Set the Guzzle cache to 0 - so roles update right away
        Guzzle::setCacheTtl(0);
        $this->websocket = new WebSocket($this->discord);
    }

    public function addPlugin($type, $command, $class, $perms, $description, $usage, $timer)
    {
        $this->$type[$command] = [
            "permissions" => $perms,
            "class" => $class,
            "description" => $description,
            "usage" => $usage,
            "timer" => $timer
        ];
    }

    public function run()
    {
        $this->websocket->on("ready", function (Discord $discord) {
            $this->log->addInfo("Websocket connected..");

            // Update our presence status
            $game = new Game(array("name" => $this->config->get("presence", "bot", "table flippin'"), "url" => null, "type" => null), true);
            $this->websocket->updatePresence($game, false);

            // Setup the timers for the timer plugins
            $config = $this->db->query("SELECT settings FROM settings");
            $that = $this;
            foreach ($this->onTimer as $command => $data) {
                $this->websocket->loop->addPeriodicTimer($data["timer"], function () use ($data, $discord, $that, $config) {
                    $data["class"]::onTimer($discord, $this->container, $config);
                });
            }

            // Foreach guild we'll setup a new cleverbot instance, if one doesn't already exist.
            /** @var Guild $guild */
            foreach ($discord->guilds->all() as $guild) {
                $serverID = $guild->id;
                $result = $this->curl->post("https://cleverbot.io/1.0/create", ["user" => $this->config->get("user", "cleverbot"), "key" => $this->config->get("key", "cleverbot")]);

                if ($result) {
                    $result = @json_decode($result);
                    $nick = isset($result->nick) ? $result->nick : false;

                    if ($nick)
                        $this->db->execute("INSERT INTO cleverbot (serverID, nick) VALUES (:serverID, :nick) ON DUPLICATE KEY UPDATE nick = :nick", [":serverID" => $serverID, ":nick" => $nick]);
                }
            }
        });

        $this->websocket->on("error", function ($error, $websocket) {
            $this->log->addError("An error occured on the websocket", [$error->getMessage()]);
        });

        $this->websocket->on("close", function ($opCode, $reason) {
            $this->log->addWarning("Websocket got closed", ["code" => $opCode, "reason" => $reason]);
        });

        $this->websocket->on("reconnecting", function () {
            $this->log->addInfo("Websocket is reconnecting..");
        });

        $this->websocket->on("reconnected", function () {
            $this->log->addInfo("Websocket was reconnected..");
        });

        // Handle incoming message logging
        $this->websocket->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) {
            // Ignore if it's from ourselves
            if ($message->author->id != $discord->user->id) {
                $this->log->addNotice("Message from {$message->author->username}", [$message->content]);
                $this->users->set($message->author->id, $message->author->username, "online", "", date("Y-m-d H:i:s"), date("Y-m-d H:i:s"), $message->content);
            }
        });

        // Handle plugin running
        $this->websocket->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) {
            $guildID = $message->full_channel->guild->id;

            // Get server config
            $config = $this->settings->get($guildID);

            // Is the person admin?
            $userDiscordID = $message->author->id;
            foreach ($this->config->get("admins", "permissions") as $admins)
                $message->isAdmin = $admins == $userDiscordID ? true : false;

            // Define the prefix if it isn't already set..
            $config->prefix = isset($config->prefix) ? $config->prefix : $this->config->get("prefix", "bot");

            // Check if the user requested an onMessage plugin
            if (substr($message->content, 0, strlen($config->prefix)) == $config->prefix) {
                foreach ($this->onMessage as $command => $data) {
                    $parts = [];
                    $content = explode(" ", $message->content);

                    foreach ($content as $index => $c) {
                        foreach (explode("\n", $c) as $p) {
                            $parts[] = $p;
                        }
                    }

                    if ($parts[0] == $config->prefix . $command) {
                        $ownerID = $message->getChannelAttribute()->getGuildAttribute()->owner_id;
                        if ($message->author->id == $ownerID)
                            $userPerms = 2;
                        else
                            $userPerms = $this->permissions->get($message->author->id, $guildID) ? $this->permissions->get($message->author->id, $guildID) : 1;

                        if ($userPerms >= $data["permissions"]) {
                            $message->getChannelAttribute()->broadcastTyping();
                            if ($data["class"] == "\\Sovereign\\Plugins\\help") {
                                $this->showHelp($message, $config);
                            } else {
                                $this->pool->submit(new $data["class"]($message, $discord, $this->log, $config, $this->db, $this->curl, $this->settings, $this->permissions, $this->container["serverConfig"], $this->users, $this->container["wolframAlpha"], $this->startTime));
                            }
                            $this->log->addInfo("{$message->author->username}#{$message->author->discriminator} ({$message->author}) ran command {$config->prefix}{$command}", $content);
                        }
                    }
                }
            }
        });

        // Handle if it's a message for the bot (Cleverbot invocation)
        $this->websocket->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) {
            // If we got highlighted we should probably answer back
            if (stristr($message->content, $discord->getClient()->id)) {
                $this->pool->submit(new cleverBotMessage($message, $discord, $this->log, $this->config, $this->db, $this->curl, $this->settings, $this->permissions, $this->container["serverConfig"], $this->users, $this->container["wolframAlpha"]));
            }
        });

        // Handle presence updates
        $this->websocket->on(Event::PRESENCE_UPDATE, function (PresenceUpdate $presenceUpdate) {
            if ($presenceUpdate->user->id && $presenceUpdate->user->username) {
                $this->log->addInfo("Updating presence info for {$presenceUpdate->user->username}");
                $game = $presenceUpdate->getGameAttribute()->name ? $presenceUpdate->getGameAttribute()->name : "";
                $this->users->set($presenceUpdate->user->id, $presenceUpdate->user->username, $presenceUpdate->status, $game, date("Y-m-d H:i:s"), null, null);
            }
        });

        // Handle guild updates (Create a new cleverbot \nick\ for this new guild)
        $this->websocket->on(Event::GUILD_UPDATE, function (Message $message, Discord $discord) {
            $this->log->addNotice("Guild update?");
            var_dump($message);
            var_dump($discord);
        });

        // Run the websocket, and in turn, the bot!
        $this->websocket->run();
    }

    public function showHelp(Message $message, $config)
    {
        if (isset($cmd)) {
            foreach ($this->onMessage as $command => $data) {
                if ($command == $cmd) {
                    $message->reply("**{$config->prefix}{$command}** _{$data["usage"]}_\r\n {$data["description"]}");
                }
            }
        } else {
            $msg = "**Commands:** \r\n";
            foreach ($this->onMessage as $command => $data) {
                $msg .= "**{$config->prefix}{$command}** | ";
            }

            $message->reply($msg);
        }
    }
}

class cleverBotMessage extends \Threaded implements \Collectable
{
    /** @var Message $message */
    private $message;
    /** @var Discord $discord */
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

    public function __construct($message, $discord, $log, $config, $db, $curl, $settings, $permissions, $serverConfig, $users, $wolframAlpha)
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
    }

    public function run()
    {
        $guildID = $this->message->getFullChannelAttribute()->guild_id;

        $cleverBotNick = $this->db->queryField("SELECT nick FROM cleverbot WHERE serverID = :serverID", "nick", array(":serverID" => $guildID));

        $msg = str_replace("<@{$this->discord->getClient()->id}>", $this->discord->getClient()->username, $this->message->content);
        $response = $this->curl->post("https://cleverbot.io/1.0/ask", array("user" => $this->config->get("user", "cleverbot"), "key" => $this->config->get("key", "cleverbot"), "nick2 => $cleverBotNick", "text" => $msg));

        if ($response) {
            $resp = @json_decode($response);
            $reply = isset($resp->response) ? $resp->response : false;
            if ($reply) {
                $this->message->getChannelAttribute()->broadcastTyping();
                $this->message->reply($reply);
            }
        }
    }
}