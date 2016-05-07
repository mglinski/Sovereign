<?php

namespace Sovereign\Plugins;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Sovereign\Sovereign;

class tq {
    public static function onMessage(Message $message, Discord $discord, $config, Sovereign $bot) {
        $container = $bot->getContainer();
        $crestData = json_decode($container["curl"]->get("https://crest-tq.eveonline.com/"), true);
        $tqStatus = isset($crestData["serviceStatus"]["eve"]) ? $crestData["serviceStatus"]["eve"] : "offline";
        $tqOnline = (int)$crestData["userCounts"]["eve"];
        $msg = "**TQ Status:** {$tqStatus} with {$tqOnline} users online.";
        $message->reply($msg);
    }

    public function onStart() {

    }

    public function onTimer() {

    }

    public function information() {
        return (object) array(
            "description" => "Tells you the current status of Tranquility",
            "usage" => "",
            "permission" => 1//1 is everyone, 2 is only admin
        );
    }
}