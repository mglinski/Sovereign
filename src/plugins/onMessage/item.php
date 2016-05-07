<?php

namespace Sovereign\Plugins;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Sovereign\Sovereign;

class item {
    public static function onMessage(Message $message, Discord $discord, $config, Sovereign $bot) {
        $container = $bot->getContainer();
        $explode = explode(" ", $message->content);
        unset($explode[0]);
        $item = implode(" ", $explode);

        if(is_numeric($item)) {
            $data = $container["db"]->queryRow("SELECT * FROM invTypes WHERE typeID = :typeID", array(":typeID" => $item));
        } else {
            $data = $container["db"]->queryRow("SELECT * FROM invTypes WHERE typeName = :typeName", array(":typeName" => $item));
        }

        if($data) {
            $msg = "```";
            foreach($data as $key => $value)
                $msg .= $key . ": " . $value . "\n";
            $msg .= "```";

            $message->reply($msg);
        }

    }

    public function onStart() {

    }

    public function onTimer() {

    }

    public function information() {
        return (object) array(
            "description" => "Shows you all the information available in the database, for an item",
            "usage" => "<itemName>",
            "permission" => 1//1 is everyone, 2 is only admin
        );
    }
}