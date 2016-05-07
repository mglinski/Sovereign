<?php

namespace Sovereign\Plugins;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Sovereign\Sovereign;

class memory {
    public static function onMessage(Message $message, Discord $discord, $config, Sovereign $bot) {
    }

    public static function onStart(Discord $discord, Sovereign $bot, $config) {

    }

    public static function onTimer(Discord $discord, Sovereign $bot, $config) {
        $bot->getContainer()["log"]->addInfo("Memory in use before garbage collection: " . memory_get_usage() / 1024 / 1024 . "MB");
        gc_collect_cycles();
        $bot->getContainer()["log"]->addInfo("Memory in use after garbage collection: " . memory_get_usage() / 1024 / 1024 . "MB");
    }

    public function information() {
        return (object) array(
            "description" => "",
            "usage" => "",
            "permission" => 1,
            "timer" => 1800
        );
    }
}