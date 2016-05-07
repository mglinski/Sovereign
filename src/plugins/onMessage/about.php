<?php

namespace Sovereign\Plugins;

use DateTime;
use Discord\Discord;
use Discord\Parts\Channel\Message;
use Sovereign\Sovereign;

class about {
    public static function onMessage(Message $message, Discord $discord, $config, Sovereign $bot) {
        $container = $bot->getContainer();
        $startTime = $container["startTime"];
        $time1 = new DateTime(date("Y-m-d H:i:s", $startTime));
        $time2 = new DateTime(date("Y-m-d H:i:s"));
        $interval = $time1->diff($time2);

        $msg = "```I am the vanguard of your destruction. This exchange is just beginning...

Author: Karbowiak (Discord ID: 118440839776174081)
Library: DiscordPHP (https://github.com/teamreflex/DiscordPHP\)
Current Version: 0.0000000000
Github Repo: https://github.com/karbowiak/Sovereign\

Statistics:
Memory Usage: ~" . round(memory_get_usage() / 1024 / 1024, 3) . "MB
Uptime: " . $interval->y . " Year(s), " .$interval->m . " Month(s), " . $interval->d ." Days, ". $interval->h . " Hours, " . $interval->i." Minutes, ".$interval->s." seconds.
```";
        $message->reply($msg);
    }

    public function onStart() {

    }

    public function onTimer() {

    }

    public function information() {
        return (object) array(
            "description" => "Shows information about the bot and it's creator",
            "usage" => "",
            "permission" => 1//1 is everyone, 2 is only admin
        );
    }
}