<?php

namespace Sovereign\Plugins;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Sovereign\Sovereign;

class user {
    public static function onMessage(Message $message, Discord $discord, $config, Sovereign $bot) {
        $container = $bot->getContainer();
        $explode = explode(" ", $message->content);
        $name = isset($explode[1]) ? $explode[1] : "";
        $name = stristr($name, "@") ? str_replace("<@", "", str_replace(">", "", $name)) : $name;

        if($name) {
            $data = $container["db"]->queryRow("SELECT * FROM users WHERE (nickName = :name OR discordID = :name)", array(":name" => $name));
            if($data) {
                $msg = "```ID: {$data["discordID"]}\nName: {$data["nickName"]}\nLast Seen: {$data["lastSeen"]}\nLast Spoken: {$data["lastSpoke"]}\nLast Status: {$data["lastStatus"]}\nPlayed Last: {$data["game"]}```";
                $message->reply($msg);
            } else {
                $message->reply("Error, no such user has been seen..");
            }
        }

    }

    public function onStart() {

    }

    public function onTimer() {

    }

    public function information() {
        return (object) array(
            "description" => "Tells you discord information on a user. Including when the bot last saw them, saw them speak, and what they were last playing",
            "usage" => "<discordName>",
            "permission" => 1//1 is everyone, 2 is only admin
        );
    }
}