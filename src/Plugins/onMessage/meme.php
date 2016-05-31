<?php

namespace Sovereign\Plugins\onMessage;

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

class meme extends \Threaded implements \Collectable
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
        $memes = array(
            'dank meme',
            '>mfw no gf',
            "m'lady *tip*",
            'le toucan has arrived',
            "jet juel can't melt dank memes",
            '༼ つ ◕_◕ ༽つ gibe',
            'ヽ༼ຈل͜ຈ༽ﾉ raise your dongers ヽ༼ຈل͜ຈ༽ﾉ',
            'ヽʕ •ᴥ•ʔﾉ raise your koalas ヽʕ﻿ •ᴥ•ʔﾉ',
            'ಠ_ಠ',
            '(－‸ლ)',
            '( ͡° ͜ʖ ͡°)',
            '( ° ͜ʖ͡°)╭∩╮',
            '(╯°□°）╯︵ ┻━┻',
            '┬──┬ ノ( ゜-゜ノ)',
            '•_•) ( •_•)>⌐■-■ (⌐■_■)',
            "i dunno lol ¯\\(°_o)/¯",
            "how do i shot web ¯\\(°_o)/¯",
            '(◕‿◕✿)',
            'ヾ(〃^∇^)ﾉ',
            '＼(￣▽￣)／',
            '(ﾉ◕ヮ◕)ﾉ*:･ﾟ✧',
            'ᕕ( ͡° ͜ʖ ͡°)ᕗ',
            'ᕕ( ᐛ )ᕗ ᕕ( ᐛ )ᕗ ᕕ( ᐛ )ᕗ',
            "(ﾉ◕ヮ◕)ﾉ *:･ﾟ✧ SO KAWAII ✧･:* \\(◕ヮ◕\\)",
            'ᕙ༼ຈل͜ຈ༽ᕗ. ʜᴀʀᴅᴇʀ,﻿ ʙᴇᴛᴛᴇʀ, ғᴀsᴛᴇʀ, ᴅᴏɴɢᴇʀ .ᕙ༼ຈل͜ຈ༽ᕗ',
            "(∩ ͡° ͜ʖ ͡°)⊃━☆ﾟ. * ･ ｡ﾟyou've been touched by the donger fairy",
            '(ง ͠° ͟ل͜ ͡°)ง ᴍᴀsᴛᴇʀ ʏᴏᴜʀ ᴅᴏɴɢᴇʀ, ᴍᴀsᴛᴇʀ ᴛʜᴇ ᴇɴᴇᴍʏ (ง ͠° ͟ل͜ ͡°)ง',
            "(⌐■_■)=/̵͇̿̿/'̿'̿̿̿ ̿ ̿̿ ヽ༼ຈل͜ຈ༽ﾉ keep your dongers where i can see them",
            '[̲̅$̲̅(̲̅ ͡° ͜ʖ ͡°̲̅)̲̅$̲̅] do you have change for a donger bill [̲̅$̲̅(̲̅ ͡° ͜ʖ ͡°̲̅)̲̅$̲̅]',
            '╰( ͡° ͜ʖ ͡° )つ──☆*:・ﾟ clickty clack clickty clack with this chant I summon spam to the chat',
            'work it ᕙ༼ຈل͜ຈ༽ᕗ harder make it (ง •̀_•́)ง better do it ᕦ༼ຈل͜ຈ༽ᕤ faster raise ur ヽ༼ຈل͜ຈ༽ﾉ donger',
        );
        $this->message->reply($memes[array_rand($memes)]);

        // Mark this as garbage
        $this->isGarbage();
    }
}