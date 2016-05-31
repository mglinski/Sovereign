<?php
namespace Sovereign;


use Discord\Cache\Cache;
use Discord\Cache\Drivers\ArrayCacheDriver;
use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\Guild;
use Discord\Parts\User\Game;
use Discord\Parts\WebSockets\PresenceUpdate;
use Discord\WebSockets\Event;
use Discord\WebSockets\WebSocket;
use Monolog\Logger;
use Pimple\Container;
use Sovereign\Lib\Config as globalConfig;
use Sovereign\Lib\cURL;
use Sovereign\Lib\Db;
use Sovereign\Lib\Permissions;
use Sovereign\Lib\Settings;
use Sovereign\Lib\Users;
use Sovereign\Plugins\onMessage\cleverBotMessage;

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
     * @var globalConfig
     */
    protected $globalConfig;
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
    private $onVoice = [];
    /**
     * @var array
     */
    private $onTimer = [];
    /**
     * @var \Pool
     */
    private $pool;
    /**
     * @var \Pool
     */
    private $timers;
    /**
     * @var \Pool
     */
    public $voice;
    /**
     * @var array
     */
    private $audioStreams;
    /**
     * @var array
     */
    private $extras = [];

    /**
     * Sovereign constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->log = $container["log"];
        $this->globalConfig = $container["config"];
        $this->db = $container["db"];
        $this->curl = $container["curl"];
        $this->settings = $container["settings"];
        $this->permissions = $container["permissions"];
        $this->users = $container["users"];
        $this->extras["startTime"] = time();
        $this->extras["memberCount"] = 0;
        $this->extras["guildCount"] = 0;
        $this->pool = new \Pool(24, \Worker::class);
        $this->timers = new \Pool(4, \Worker::class);
        $this->voice = new \Pool(24, \Worker::class);

        // Init Discord and Websocket
        $this->log->addInfo("Initializing Discord and Websocket connections..");
        $this->discord = Discord::createWithBotToken($this->globalConfig->get("token", "bot"));
        Cache::setCache(new ArrayCacheDriver());
        $this->websocket = new WebSocket($this->discord);
    }

    /**
     * @param $type
     * @param $command
     * @param $class
     * @param $perms
     * @param $description
     * @param $usage
     * @param $timer
     */
    public function addPlugin($type, $command, $class, $perms, $description, $usage, $timer)
    {
        $this->log->addInfo("Adding plugin: {$command}");
        $this->$type[$command] = [
            "permissions" => $perms,
            "class" => $class,
            "description" => $description,
            "usage" => $usage,
            "timer" => $timer
        ];
    }

    /**
     *
     */
    public function run()
    {
        // Reap the threads!
        $this->websocket->loop->addPeriodicTimer(600, function() {
            // Only restart the audioStream pool if it's actually empty..
            if (empty($this->voice)) {
                $this->voice->shutdown();
                $this->voice = new \Pool(24, \Worker::class);
            }
                
            $this->log->addInfo("Restarting the threading pool, to clear out old threads..");

            // Shutdown the pool
            $this->pool->shutdown();
            $this->timers->shutdown();

            // Startup the pool again
            $this->pool = new \Pool(24, \Worker::class);
            $this->timers = new \Pool(4, \Worker::class);
        });

        // Handle the onReady event, and setup some timers and so forth
        $this->websocket->on("ready", function(Discord $discord) {
            $this->log->addInfo("Websocket connected..");

            // Update our presence status
            $game = new Game(array("name" => $this->globalConfig->get("presence", "bot", "table flippin'"), "url" => null, "type" => null), true);
            $this->websocket->updatePresence($game, false);

            // Count the amount of people we are available to..
            /** @var Guild $guild */
            foreach ($this->discord->getClient()->getGuildsAttribute()->all() as $guild) {
                $this->extras["memberCount"] += $guild->member_count;
                $this->extras["guildCount"] = $this->extras["guildCount"] + 1;
                $this->extras["guild"]["memberCount"]["id{$guild->id}"] = $guild->member_count;
                $this->extras["onMessagePlugins"] = $this->onMessage;
                $this->extras["onVoicePlugins"] = $this->onVoice;
            }
            
            $this->log->addInfo("Member count, currently available to: {$this->extras["memberCount"]} people");

            // Setup the timers for the timer plugins
            foreach ($this->onTimer as $command => $data) {
                $this->websocket->loop->addPeriodicTimer($data["timer"], function() use ($data, $discord) {
                    try {
                        $plugin = new $data["class"]($discord, $this->log, $this->globalConfig, $this->db, $this->curl, $this->settings, $this->permissions, $this->container["serverConfig"], $this->users, $this->extras);
                        $this->timers->submit($plugin);
                    } catch (\Exception $e) {
                        $this->log->addError("Error running the periodic timer: {$e->getMessage()}");
                    }
                });
            }

            // Issue periodically member recount
            $this->websocket->loop->addPeriodicTimer(600, function() {
                $this->extras["memberCount"] = 0;
                $this->extras["guildCount"] = 0;
                /** @var Guild $guild */
                foreach ($this->discord->getClient()->getGuildsAttribute()->all() as $guild) {
                    $this->extras["memberCount"] += $guild->member_count;
                    $this->extras["guildCount"] += 1;
                    $this->extras["guild"]["memberCount"]["id{$guild->id}"] = $guild->member_count;
                    $this->extras["onMessagePlugins"] = $this->onMessage;
                    $this->extras["onVoicePlugins"] = $this->onVoice;
                }

                $this->log->addInfo("Member recount, currently available to: {$this->extras["memberCount"]} people");
            });

            // @todo run a timer to check if there are any active voice sessions - and if there are, if there are any people in those voice sessions
            // If not, stop the session and leave the channel (To save some bandwidth)
        });

        $this->websocket->on("error", function($error, $websocket) {
            $this->log->addError("An error occurred on the websocket", [$error->getMessage()]);
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
            $this->log->addInfo("Message from {$message->author->username}", [$message->content]);

            // Don't update data for ourselves..
            if ($message->author->id != $discord->getClient()->id) {
                            $this->users->set($message->author->id, $message->author->username, "online", null, date("Y-m-d H:i:s"), date("Y-m-d H:i:s"), $message->content);
            }

            // @todo Create text logs
        });

        // Handle plugin running
        $this->websocket->on(Event::MESSAGE_CREATE, function(Message $message, Discord $discord) {
            $guildID = $message->getChannelAttribute()->guild_id;

            // Get server config
            $config = $this->settings->get($guildID);

            // Is the person admin?
            $userDiscordID = $message->author->id;
            foreach ($this->globalConfig->get("admins", "permissions") as $admins) {
                            $message->isAdmin = $admins == $userDiscordID ? true : false;
            }

            // Define the prefix if it isn't already set..
            @$config->prefix = isset($config->prefix) ? $config->prefix : $this->globalConfig->get("prefix", "bot");

            // Check if the user requested an onMessage plugin
            if (substr($message->content, 0, strlen($config->prefix)) == $config->prefix) {
                foreach ($this->onMessage as $command => $data) {
                    $parts = [];
                    $content = explode(" ", $message->content);
                    foreach ($content as $index => $c) {
                                            foreach (explode("\n", $c) as $p)
                            $parts[] = $p;
                    }

                    if ($parts[0] == $config->prefix . $command) {
                        // If they are listed under the admins array in the bot config, they're the super admins
                        if (in_array($message->author->id, $this->globalConfig->get("admins", "permissions"))) {
                                                    $userPerms = 3;
                        }
                        // If they are guild owner, they're automatically getting permission level 2
                        elseif (isset($message->getChannelAttribute()->getGuildAttribute()->owner_id) && ($message->author->id == $message->getChannelAttribute()->getGuildAttribute()->owner_id)) {
                                                    $userPerms = 2;
                        }
                        // Everyone else are just users
                        else {
                                                    $userPerms = 1;
                        }

                        if ($userPerms >= $data["permissions"]) {
                            try {
                                $message->getChannelAttribute()->broadcastTyping();
                                if (in_array($data["class"], array("\\Sovereign\\Plugins\\onMessage\\auth"))) {
                                    /** @var \Threaded $plugin */
                                    $plugin = new $data["class"]($message, $discord, $config, $this->log, $this->globalConfig, $this->db, $this->curl, $this->settings, $this->permissions, $this->container["serverConfig"], $this->users, $this->extras);
                                    $plugin->run();
                                } else {
                                    /** @var \Threaded $plugin */
                                    $plugin = new $data["class"]($message, $discord, $config, $this->log, $this->globalConfig, $this->db, $this->curl, $this->settings, $this->permissions, $this->container["serverConfig"], $this->users, $this->extras);
                                    $this->pool->submit($plugin);
                                }
                                $this->log->addInfo("{$message->author->username}#{$message->author->discriminator} ({$message->author}) ran command {$config->prefix}{$command}", $content);
                            } catch (\Exception $e) {
                                $this->log->addError("Error running command {$config->prefix}{$command}. Command run by {$message->author->username} in {$message->getChannelAttribute()->name}. Error: {$e->getMessage()}");
                                $message->reply("**Error:** There was a problem running the command: {$e->getMessage()}");
                            }
                        }
                    }
                }
            }
        });

        // Handle joining a voice channel, and playing.. stuff....
        $this->websocket->on(Event::MESSAGE_CREATE, function(Message $message, Discord $discord) {
            // Get the guildID
            $guildID = $message->getChannelAttribute()->guild_id;
            
            // Get this guilds settings
            $config = $this->settings->get($guildID);
            
            // Get the prefix for this guild
            @$config->prefix = isset($config->prefix) ? $config->prefix : $this->globalConfig->get("prefix", "bot");

            if (substr($message->content, 0, strlen($config->prefix)) == $config->prefix) {
                foreach ($this->onVoice as $command => $data) {
                    $parts = [];
                    $content = explode(" ", $message->content);
                    foreach ($content as $index => $c) {
                                            foreach (explode("\n", $c) as $p) {
                                                                        $parts[] = $p;
                                            }
                    }

                    if ($parts[0] == $config->prefix . $command) {
                        try {
                            $voiceChannels = $message->getFullChannelAttribute()->getGuildAttribute()->channels->getAll("type", "voice");
                            foreach ($voiceChannels as $channel) {
                                if (!empty($channel->members[$message->author->id])) {
                                    $voice = new $data["class"]();
                                    $voice->run($message, $discord, $this->websocket, $this->log, $this->audioStreams, $channel, $this->curl);
                                }
                            }
                        } catch (\Exception $e) {
                            $this->log->addError("Error running voice command {$config->prefix}{$command}. Command run by {$message->author->username} in {$message->getChannelAttribute()->name}. Error: {$e->getMessage()}");
                            $message->reply("**Error:** There was a problem running the command: {$e->getMessage()}");
                        }
                    }
                }
            }
        });

        // Handle if it's a message for the bot (CleverBot invocation)
        $this->websocket->on(Event::MESSAGE_CREATE, function(Message $message, Discord $discord) {
            // If we got highlighted we should probably answer back
            if (stristr($message->content, $discord->getClient()->id)) {
                try {
                    $this->pool->submit(new cleverBotMessage($message, $discord, $this->log, $this->globalConfig, $this->db, $this->curl, $this->settings, $this->permissions, $this->container["serverConfig"], $this->users));
                } catch (\Exception $e) {
                    $message->reply("**Error:** There was an error with CleverBot: {$e->getMessage()}");
                }
            }
        });

        // Handle presence updates
        $this->websocket->on(Event::PRESENCE_UPDATE, function(PresenceUpdate $presenceUpdate) {
            if ($presenceUpdate->user->id && $presenceUpdate->user->username) {
                try {
                    $this->log->addInfo("Updating presence info for {$presenceUpdate->user->username}");
                    $game = isset($presenceUpdate->getGameAttribute()->name) ? $presenceUpdate->getGameAttribute()->name : null;
                    $this->users->set($presenceUpdate->user->id, $presenceUpdate->user->username, $presenceUpdate->status, $game, date("Y-m-d H:i:s"), null, null);
                } catch (\Exception $e) {
                    $this->log->addError("Error: {$e->getMessage()}");
                }
            }
        });

        // Create a new cleverbot \nick\ for this new guild
        $this->websocket->on(Event::GUILD_CREATE, function(Guild $guild) {
            $this->log->addInfo("Setting up Cleverbot for {$guild->name}");
            $serverID = $guild->id;
            $result = $this->curl->post("https://cleverbot.io/1.0/create", ["user" => $this->globalConfig->get("user", "cleverbot"), "key" => $this->globalConfig->get("key", "cleverbot")]);

            if ($result) {
                $result = @json_decode($result);
                $nick = isset($result->nick) ? $result->nick : false;

                if ($nick) {
                                    $this->db->execute("INSERT INTO cleverbot (serverID, nick) VALUES (:serverID, :nick) ON DUPLICATE KEY UPDATE nick = :nick", [":serverID" => $serverID, ":nick" => $nick]);
                }
            }

            // Send a hello message to the channel (Only if it's new!)
            //$message = "Hello, i was invited here by someone with admin permissions, i have quite a few features that you can discover by doing %help\n";
            //$message .= "I am sorry if i am triggering other bots aswell, you can change my trigger with %config setTrigger newTrigger (Example: %config setTrigger *)\n";
            //$message .= "If you for some reason don't want me here afterall, just kick me ;)";
            // Get the first channel in the list (usually the default channel)
            //$channel = $guild->channels->first();
            //$channel->sendMessage($message);
        });

        // Run the websocket, and in turn, the bot!
        $this->websocket->run();
    }
}