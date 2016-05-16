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
use Sovereign\Lib\Config;
use Sovereign\Lib\cURL;
use Sovereign\Lib\Db;
use Sovereign\Lib\Permissions;
use Sovereign\Lib\ServerConfig;
use Sovereign\Lib\Settings;
use Sovereign\Lib\Users;

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
     * @var array
     */
    private $extras = [];
    /**
     * @var array
     */
    private $activeVoice = [];

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
        $this->extras["startTime"] = time();
        $this->pool = new \Pool(24, \Worker::class);

        // @todo Fire up the onStart plugins

        // Init Discord and Websocket
        $this->log->addInfo("Initializing Discord and Websocket connections..");
        $this->discord = Discord::createWithBotToken($this->config->get("token", "bot"));
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
        //$this->websocket->loop->addPeriodicTimer(600, function() {
        //    $this->log->addInfo("Restarting the threading pool, to clear out old threads..");
        //    // Shutdown the pool
        //    $this->pool->shutdown();
        //    // Startup the pool again
        //    $this->pool = new \Pool(24, \Worker::class);
        //});

        $this->websocket->on("ready", function (Discord $discord) {
            $this->log->addInfo("Websocket connected..");

            // Update our presence status
            $game = new Game(array("name" => $this->config->get("presence", "bot", "table flippin'"), "url" => null, "type" => null), true);
            $this->websocket->updatePresence($game, false);

            // Count the amount of people we are available to..
            /** @var Guild $guild */
            foreach($this->discord->getClient()->getGuildsAttribute()->all() as $guild) {
                $this->extras["memberCount"] += $guild->member_count;
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
            // Ignore if it's from ourselves
            if ($message->author->id != $discord->getClient()->id) {
                $this->log->addNotice("Message from {$message->author->username}", [$message->content]);
                $this->users->set($message->author->id, $message->author->username, "online", null, date("Y-m-d H:i:s"), date("Y-m-d H:i:s"), $message->content);

                // @todo Create text logs
            }
        });

        // Handle plugin running
        $this->websocket->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) {
            $guildID = $message->getChannelAttribute()->guild_id;

            // Get server config
            $config = $this->settings->get($guildID);

            // Is the person admin?
            $userDiscordID = $message->author->id;
            foreach ($this->config->get("admins", "permissions") as $admins)
                $message->isAdmin = $admins == $userDiscordID ? true : false;

            // Define the prefix if it isn't already set..
            @$config->prefix = isset($config->prefix) ? $config->prefix : $this->config->get("prefix", "bot");

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
                            }
                            // Plugins that shouldn't be threaded... because they behave really fucking wonky (Can't access the Discord Cache)
                            elseif(in_array($data["class"], array("\\Sovereign\\Plugins\\auth", "\\Sovereign\\Plugins\\about", "\\Sovereign\\Plugins\\guilds"))) {
                                /** @var \Threaded $plugin */
                                $plugin = new $data["class"]($message, $discord, $config, $this->log, $this->config, $this->db, $this->curl, $this->settings, $this->permissions, $this->container["serverConfig"], $this->users, $this->extras);
                                $plugin->run();
                            }
                            else {
                                /** @var \Threaded $plugin */
                                $plugin = new $data["class"]($message, $discord, $config, $this->log, $this->config, $this->db, $this->curl, $this->settings, $this->permissions, $this->container["serverConfig"], $this->users, $this->extras);
                                $this->pool->submit($plugin);
                            }
                            $this->log->addInfo("{$message->author->username}#{$message->author->discriminator} ({$message->author}) ran command {$config->prefix}{$command}", $content);
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
            @$config->prefix = isset($config->prefix) ? $config->prefix : $this->config->get("prefix", "bot");

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
                                $file = __DIR__ . "/../sounds/horn.mp3";
                                $vc->playFile($file, 2)->done(function () use ($vc) {
                                    $vc->close();
                                });
                            });
                        });
                    }
                }
            }

            // Random sounds
            if($message->content == $config->prefix . "reapers") {
                $voiceChannel = $message->getFullChannelAttribute()->getGuildAttribute()->channels->getAll("type", "voice");
                foreach ($voiceChannel as $channel) {
                    if (isset($channel->members[$message->author->id])) {
                        $this->websocket->joinVoiceChannel($channel)->then(function (VoiceClient $vc) use ($message, $discord) {
                            $vc->setFrameSize(20)->then(function () use ($vc) {
                                $vc->setBitrate(128);
                                $num = rand(1, 23);
                                $file = __DIR__ . "/../sounds/{$num}.mp3";
                                $vc->playFile($file, 2)->done(function () use ($vc) {
                                    $vc->close();
                                });
                            });
                        });
                    }
                }
            }
        });

        // Handle if it's a message for the bot (Cleverbot invocation)
        $this->websocket->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) {
            // If we got highlighted we should probably answer back
            if (stristr($message->content, $discord->getClient()->id))
                $this->pool->submit(new cleverBotMessage($message, $discord, $this->log, $this->config, $this->db, $this->curl, $this->settings, $this->permissions, $this->container["serverConfig"], $this->users));
        });

        // Handle presence updates
        $this->websocket->on(Event::PRESENCE_UPDATE, function (PresenceUpdate $presenceUpdate) {
            if ($presenceUpdate->user->id && $presenceUpdate->user->username) {
                $this->log->addInfo("Updating presence info for {$presenceUpdate->user->username}");
                //$game = $presenceUpdate->getGameAttribute();
                $this->users->set($presenceUpdate->user->id, $presenceUpdate->user->username, $presenceUpdate->status, null, date("Y-m-d H:i:s"), null, null);
            }
        });

        // Handle guild updates (Create a new cleverbot \nick\ for this new guild)
        $this->websocket->on(Event::GUILD_CREATE, function (Guild $guild) {
            $this->log->addInfo("Setting up Cleverbot for {$guild->name}");
            $serverID = $guild->id;
            $result = $this->curl->post("https://cleverbot.io/1.0/create", ["user" => $this->config->get("user", "cleverbot"), "key" => $this->config->get("key", "cleverbot")]);

            if ($result) {
                $result = @json_decode($result);
                $nick = isset($result->nick) ? $result->nick : false;

                if ($nick)
                    $this->db->execute("INSERT INTO cleverbot (serverID, nick) VALUES (:serverID, :nick) ON DUPLICATE KEY UPDATE nick = :nick", [":serverID" => $serverID, ":nick" => $nick]);
            }
        });

        $this->websocket->on(Event::GUILD_UPDATE, function(Message $message, Discord $discord) {
            $this->log->addInfo("Seems a guild update was triggered????");
            var_dump($message);
        });

        // Run the websocket, and in turn, the bot!
        $this->websocket->run();
    }

    /**
     * Shows the help message
     *
     * @param Message $message
     * @param $config
     */
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

/**
 * Class cleverBotMessage
 * @package Sovereign
 */
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
     * cleverBotMessage constructor.
     * @param $message
     * @param $discord
     * @param $log
     * @param $config
     * @param $db
     * @param $curl
     * @param $settings
     * @param $permissions
     * @param $serverConfig
     * @param $users
     * @param $wolframAlpha
     */
    public function __construct($message, $discord, $log, $config, $db, $curl, $settings, $permissions, $serverConfig, $users)
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
    }

    /**
     *
     */
    public function run()
    {
        $guildID = $this->message->getFullChannelAttribute()->guild_id;

        $cleverBotNick = $this->db->queryField("SELECT nick FROM cleverbot WHERE serverID = :serverID", "nick", array(":serverID" => $guildID));

        $msg = str_replace("<@{$this->discord->getClient()->id}>", $this->discord->getClient()->username, $this->message->content);
        $response = $this->curl->post("https://cleverbot.io/1.0/ask", array("user" => $this->config->get("user", "cleverbot"), "key" => $this->config->get("key", "cleverbot"), "nick" => $cleverBotNick, "text" => $msg));

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