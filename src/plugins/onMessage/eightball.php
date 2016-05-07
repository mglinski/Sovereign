<?php

namespace Sovereign\Plugins;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Sovereign\Sovereign;

class eightball {
    public static function onMessage(Message $message, Discord $discord, $config, Sovereign $bot) {
        $choices = array(
            'It is certain',
            'It is decidedly so',
            'Without a doubt',
            'Yes, definitely',
            'You may rely on it',
            'As I see it, yes',
            'Most likely',
            'More than likely',
            'Outlook good',
            'Yes',
            'No',
            'Lol no',
            'Signs point to, yes',
            'Reply hazy, try again',
            'Ask again later',
            'I Better not tell you now',
            'I Cannot predict now',
            'Concentrate and ask again',
            'Don\'t count on it',
            'My reply is no',
            'My sources say no',
            'Outlook not so good',
            'Very doubtful',
        );

        $message->reply($choices[array_rand($choices)]);
    }

    public function onStart() {

    }

    public function onTimer() {

    }

    public function information() {
        return (object) array(
            "description" => "Shakes the eightball, and gives you a reply",
            "usage" => "",
            "permission" => 1//1 is everyone, 2 is only admin
        );
    }
}