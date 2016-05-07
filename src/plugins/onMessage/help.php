<?php

namespace Sovereign\Plugins;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Sovereign\Sovereign;

class help {
    public static function onMessage(Message $message, Discord $discord, $config, Sovereign $bot) {
        $explode = explode(" ", $message->content);
        $cmd = isset($explode[1]) ? $explode[1] : null;

        if(isset($cmd)) {
            foreach($bot->getOnMessagePlugins() as $command => $data) {
                if($command == $cmd) {
                    $message->reply("**{$config->prefix}{$command}** _{$data["usage"]}_\r\n {$data["description"]}");
                }
            }
        } else {
            $msg = "**Commands:** \r\n";
            foreach ($bot->getOnMessagePlugins() as $command => $data) {
                $msg .= "**{$config->prefix}{$command}** | ";
            }

            $message->reply($msg);
        }
    }

    public function onStart() {

    }

    public function onTimer() {

    }

    public function information() {
        return (object) array(
            "description" => "Shows helpful information. Commands available and help information for a single plugin",
            "usage" => "<command> (Optional)",
            "permission" => 1//1 is everyone, 2 is only admin
        );
    }
}