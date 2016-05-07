<?php

namespace Sovereign\Plugins;

use DateTime;
use Discord\Discord;
use Discord\Parts\Channel\Message;
use Sovereign\Sovereign;

class listen {
    public static function onMessage(Message $message, Discord $discord, $config, Sovereign $bot) {
        
    }

    public function onStart() {

    }

    public function onTimer() {

    }

    public function information() {
        return (object) array(
            "description" => "Creates a socket for this specific channel, where you can send text to pipe into the channel",
            "usage" => "",
            "permission" => 2//1 is everyone, 2 is only admin
        );
    }
}