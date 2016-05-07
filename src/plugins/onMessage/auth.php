<?php

namespace Sovereign\Plugins;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Sovereign\Sovereign;

class auth {
    public static function onMessage(Message $message, Discord $discord, $config, Sovereign $bot) {

    }

    public function onStart() {

    }

    public function onTimer() {

    }

    public function information() {
        return (object) array(
            "description" => "Authenticates you against a login with certain restrictions",
            "usage" => "<authcode>",
            "permission" => 1//1 is everyone, 2 is only admin
        );
    }
}