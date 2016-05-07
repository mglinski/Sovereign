<?php

namespace Sovereign\Plugins;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Sovereign\Sovereign;

class guilds {
    public static function onMessage(Message $message, Discord $discord, $config, Sovereign $bot) {
        $guilds = $discord->guilds->all();
        $list = "";
        foreach($guilds as $guild)
            $list .= "{$guild->name}, ";

        $message->reply("I am on the following servers: " . rtrim($list, ", "));
    }

    public function onStart() {

    }

    public function onTimer() {

    }

    public function information() {
        return (object) array(
            "description" => "Tells you what guilds (Server) the bot is on",
            "usage" => "",
            "permission" => 1//1 is everyone, 2 is only admin
        );
    }
}