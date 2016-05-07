<?php

namespace Sovereign\Plugins;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Sovereign\Sovereign;

class join {
    public static function onMessage(Message $message, Discord $discord, $config, Sovereign $bot) {
        $message->reply("Sorry, joining a server does not work - instead use the following link: https://discordapp.com/oauth2/authorize?client_id=176115483513323520&scope=bot&permissions=36703232");
    }

    public function onStart() {

    }

    public function onTimer() {

    }

    public function information() {
        return (object) array(
            "description" => "Tells you the oauth invite link",
            "usage" => "",
            "permission" => 1//1 is everyone, 2 is only admin
        );
    }
}