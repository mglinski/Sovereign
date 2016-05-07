<?php

namespace Sovereign\Plugins;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Sovereign\Sovereign;

class coinflip {
    public static function onMessage(Message $message, Discord $discord, $config, Sovereign $bot) {
        $sides = ["Heads", "Tails"];
        $message->reply("The result of the coinflip is: " . $sides[array_rand($sides)]);
    }

    public function onStart() {

    }

    public function onTimer() {

    }
    
    public function information() {
        return (object) array(
            "description" => "Flips a coin, and gives you the results",
            "usage" => "",
            "permission" => 1//1 is everyone, 2 is only admin
        );
    }
}