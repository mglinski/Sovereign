<?php
namespace Sovereign;


use Discord\Cache\Cache;
use Discord\Cache\Drivers\ArrayCacheDriver;
use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\Guild;
use Discord\Parts\User\Game;
use Discord\Parts\WebSockets\PresenceUpdate;
use Discord\Voice\VoiceClient;
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
use Sovereign\Plugins\onMessage\help;

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

        // @todo Fire up the onStart plugins

        // Init Discord and Websocket
        $this->log->addInfo("Initializing Discord and Websocket connections..");
        $this->discord = Discord::createWithBotToken($this->globalConfig->get("token", "bot"));
        //Cache::setCache(new RedisCacheDriver("127.0.0.1", 6379, null, 0));
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
        $this->log->addInfo("Enabling plugin: {$command}");
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
            $this->log->addInfo("Restarting the threading pool, to clear out old threads..");
            // Shutdown the pool
            $this->pool->shutdown();
            // Startup the pool again
            $this->pool = new \Pool(24, \Worker::class);
        });

        // Handle the onReady event, and setup some timers and so forth
        $this->websocket->on("ready", function (Discord $discord) {
            $this->log->addInfo("Websocket connected..");

            // Update our presence status
            $game = new Game(array("name" => $this->globalConfig->get("presence", "bot", "table flippin'"), "url" => null, "type" => null), true);
            $this->websocket->updatePresence($game, false);

            // Count the amount of people we are available to..
            /** @var Guild $guild */
            foreach($this->discord->getClient()->getGuildsAttribute()->all() as $guild) {
                $this->extras["memberCount"] += $guild->member_count;
                $this->extras["guildCount"] = $this->extras["guildCount"] + 1;
                $this->extras["guild"]["memberCount"]["id{$guild->id}"] = $guild->member_count;
                $this->extras["onMessagePlugins"] = $this->onMessage;
            }
            
            $this->log->addInfo("Member count, currently available to: {$this->extras["memberCount"]} people");

            // Setup the timers for the timer plugins
            $config = $this->db->query("SELECT settings FROM settings");
            $that = $this;
            foreach ($this->onTimer as $command => $data) {
                $this->websocket->loop->addPeriodicTimer($data["timer"], function () use ($data, $discord, $that, $config) {
                    // @todo thread this
                    $data["class"]::onTimer($discord, $this->container, $config);
                });
            }

            // Issue periodically member recount
            $this->websocket->loop->addPeriodicTimer(600, function() {
                $this->extras["memberCount"] = 0;
                /** @var Guild $guild */
                foreach($this->discord->getClient()->getGuildsAttribute()->all() as $guild) {
                    $this->extras["memberCount"] += $guild->member_count;
                    $this->extras["guildCount"] = $this->extras["guildCount"] + 1;
                    $this->extras["guild"]["memberCount"]["id{$guild->id}"] = $guild->member_count;
                    $this->extras["onMessagePlugins"] = $this->onMessage;
                }

                $this->log->addInfo("Member recount, currently available to: {$this->extras["memberCount"]} people");
            });
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
            $this->log->addInfo("Message from {$message->author->username}", [$message->content]);

            // Don't update data for ourselves..
            if ($message->author->id != $discord->getClient()->id)
                $this->users->set($message->author->id, $message->author->username, "online", null, date("Y-m-d H:i:s"), date("Y-m-d H:i:s"), $message->content);

            // @todo Create text logs
        });

        // Handle plugin running
        $this->websocket->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) {
            $guildID = $message->getChannelAttribute()->guild_id;

            // Get server config
            $config = $this->settings->get($guildID);

            // Is the person admin?
            $userDiscordID = $message->author->id;
            foreach ($this->globalConfig->get("admins", "permissions") as $admins)
                $message->isAdmin = $admins == $userDiscordID ? true : false;

            // Define the prefix if it isn't already set..
            @$config->prefix = isset($config->prefix) ? $config->prefix : $this->globalConfig->get("prefix", "bot");

            // Check if the user requested an onMessage plugin
            if (substr($message->content, 0, strlen($config->prefix)) == $config->prefix) {
                foreach ($this->onMessage as $command => $data) {
                    $parts = [];
                    $content = explode(" ", $message->content);
                    foreach ($content as $index => $c)
                        foreach (explode("\n", $c) as $p)
                            $parts[] = $p;

                    if ($parts[0] == $config->prefix . $command) {
                        $ownerID = $message->getChannelAttribute()->getGuildAttribute()->owner_id;
                        // If they are listed under the admins array in the bot config, they're the super admins
                        if(in_array($message->author->id, $this->globalConfig->get("admins", "permissions")))
                            $userPerms = 3;
                        // If they are guild owner, they're automatically getting permission level 2
                        elseif($message->author->id == $ownerID)
                            $userPerms = 2;
                        // Everyone else are just users
                        else
                            $userPerms = 1;
                        echo $userPerms;
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
            $guildID = $message->getChannelAttribute()->guild_id;

            // Get server config
            $config = $this->settings->get($guildID);

            // Define the prefix if it isn't already set..
            @$config->prefix = isset($config->prefix) ? $config->prefix : $this->globalConfig->get("prefix", "bot");

            // 90s Music!
            if($message->content == $config->prefix . "unleashthe90s") {
                $voiceChannel = $message->getFullChannelAttribute()->getGuildAttribute()->channels->getAll("type", "voice");
                foreach($voiceChannel as $channel) {
                    if(isset($channel->members[$message->author->id])) {
                        // Get a random song from the 90sbutton playlist
                        $playlist = json_decode($this->curl->get("http://the90sbutton.com/playlist.php"));
                        $song = $playlist[array_rand($playlist)];

                        // Now get the mp3 from
                        $songFile = __DIR__ . "/../cache/songs/{$song->youtubeid}.mp3";
                        if (!file_exists($songFile)) {
                            $this->log->addNotice("Downloading {$song->title} by {$song->artist}");
                            exec("youtube-dl https://www.youtube.com/watch?v={$song->youtubeid} -x --audio-format mp3 -q -o {$songFile}");
                        }

                        $this->websocket->joinVoiceChannel($channel)->then(function(VoiceClient $vc) use ($message, $discord, $songFile, $song, $channel) {
                            $vc->setFrameSize(20)->then(function() use ($message, $vc, $songFile, $song, $channel) {
                                $this->log->addNotice("Now Playing {$song->title} by {$song->artist}");
                                $message->reply("Now playing **{$song->title}** by **{$song->artist}** in {$channel->name}");
                                $vc->setBitrate(128);
                                $vc->playFile($songFile, 2)->done(function () use ($vc) {
                                    $vc->close();
                                });
                            });
                        });
                    }
                }
            }

            // YouTubeeee
            if(stristr($message->content, $config->prefix . "yt")) {
                $voiceChannel = $message->getFullChannelAttribute()->getGuildAttribute()->channels->getAll("type", "voice");
                foreach($voiceChannel as $channel) {
                    if(isset($channel->members[$message->author->id])) {
                        $exp = explode(" ", $message->content);
                        unset($exp[0]);
                        $youtubeLink = implode(" ", $exp);

                        // URL Checker
                        $parts = parse_url($youtubeLink);
                        if(!stristr($parts["host"], "youtube.com"))
                            return $message->reply("Error, you can only use youtube links!");

                        $md5 = md5($youtubeLink);
                        // Now get the mp3 from
                        $songFile = __DIR__ . "/../cache/songs/{$md5}.mp3";
                        if (!file_exists($songFile)) {
                            $this->log->addNotice("Downloading song from YouTube.. {$youtubeLink}");
                            exec("youtube-dl $youtubeLink -x --audio-format mp3 -q -o {$songFile}");
                        }

                        $this->websocket->joinVoiceChannel($channel)->then(function(VoiceClient $vc) use ($message, $discord, $songFile, $channel) {
                            $vc->setFrameSize(20)->then(function() use ($message, $vc, $songFile, $channel) {
                                $vc->setBitrate(128);
                                $vc->playFile($songFile, 2)->done(function() use ($vc) {
                                    $vc->close();
                                });
                            });
                        });
                    }
                }
            }

            // The Reaper Horn
            if($message->content == $config->prefix . "horn") {
                $voiceChannel = $message->getFullChannelAttribute()->getGuildAttribute()->channels->getAll("type", "voice");
                foreach($voiceChannel as $channel) {
                    if (isset($channel->members[$message->author->id])) {
                        $this->websocket->joinVoiceChannel($channel)->then(function (VoiceClient $vc) use ($message, $discord) {
                            $vc->setFrameSize(20)->then(function () use ($vc) {
                                $vc->setBitrate(128);
                                $file = __DIR__ . "/../sounds/reapers/horn.mp3";
                                $vc->playFile($file, 2)->done(function () use ($vc) {
                                    $vc->close();
                                });
                            });
                        });
                    }
                }
            }

            // Random reaper sounds
            if($message->content == $config->prefix . "reapers") {
                $voiceChannel = $message->getFullChannelAttribute()->getGuildAttribute()->channels->getAll("type", "voice");
                foreach ($voiceChannel as $channel) {
                    if (isset($channel->members[$message->author->id])) {
                        $this->websocket->joinVoiceChannel($channel)->then(function (VoiceClient $vc) use ($message, $discord) {
                            $vc->setFrameSize(20)->then(function () use ($vc) {
                                $vc->setBitrate(128);
                                $num = mt_rand(1, 23);
                                $file = __DIR__ . "/../sounds/reapers/{$num}.mp3";
                                $vc->playFile($file, 2)->done(function () use ($vc) {
                                    $vc->close();
                                });
                            });
                        });
                    }
                }
            }

            // Random EVE warning sounds
            if($message->content == $config->prefix . "warnings") {
                $voiceChannel = $message->getFullChannelAttribute()->getGuildAttribute()->channels->getAll("type", "voice");
                foreach ($voiceChannel as $channel) {
                    if (isset($channel->members[$message->author->id])) {
                        $this->websocket->joinVoiceChannel($channel)->then(function (VoiceClient $vc) use ($message, $discord) {
                            $vc->setFrameSize(20)->then(function () use ($vc) {
                                $vc->setBitrate(128);
                                $num = mt_rand(1, 6);
                                $file = __DIR__ . "/../sounds/eve/{$num}.mp3";
                                $vc->playFile($file, 2)->done(function () use ($vc) {
                                    $vc->close();
                                });
                            });
                        });
                    }
                }
            }
        });

        // Handle if it's a message for the bot (CleverBot invocation)
        $this->websocket->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) {
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
        $this->websocket->on(Event::PRESENCE_UPDATE, function (PresenceUpdate $presenceUpdate) {
            if ($presenceUpdate->user->id && $presenceUpdate->user->username) {
                try {
                    $this->log->addInfo("Updating presence info for {$presenceUpdate->user->username}");
                    //$game = $presenceUpdate->getGameAttribute();
                    $this->users->set($presenceUpdate->user->id, $presenceUpdate->user->username, $presenceUpdate->status, null, date("Y-m-d H:i:s"), null, null);
                } catch (\Exception $e) {
                    $this->log->addError("Error: {$e->getMessage()}");
                }
            }
        });

        // Create a new cleverbot \nick\ for this new guild
        $this->websocket->on(Event::GUILD_CREATE, function (Guild $guild) {
            $this->log->addInfo("Setting up Cleverbot for {$guild->name}");
            $serverID = $guild->id;
            $result = $this->curl->post("https://cleverbot.io/1.0/create", ["user" => $this->globalConfig->get("user", "cleverbot"), "key" => $this->globalConfig->get("key", "cleverbot")]);

            if ($result) {
                $result = @json_decode($result);
                $nick = isset($result->nick) ? $result->nick : false;

                if ($nick)
                    $this->db->execute("INSERT INTO cleverbot (serverID, nick) VALUES (:serverID, :nick) ON DUPLICATE KEY UPDATE nick = :nick", [":serverID" => $serverID, ":nick" => $nick]);
            }
        });

        // Run the websocket, and in turn, the bot!
        $this->websocket->run();
    }
}