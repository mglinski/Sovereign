<?php

namespace Sovereign\Plugins;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Monolog\Logger;
use Pimple\Container;
use Sovereign\Sovereign;

class jabberPingsTheCulture {

    public static function onTimer(Discord $discord, $container, $config) {
        // @todo This really needs to be made pretty - and per. server configurable - somehow.... (Maybe using sockets?)
        $data = file("/tmp/discord.db");
        /** @var Logger $log */
        $log = $container["log"];

        if($data) {
            $message = "";
            foreach($data as $row) {
                $row = str_replace("\n", "", str_replace("\r", "", str_replace("^@", "", $row)));
                if($row == "" || $row == " ")
                    continue;

                $message .= $row . " | ";
                usleep(100000);
            }

            $message = trim(substr($message, 0, -2));
            $channelID = 154221481625124864;
            $channel = \Discord\Parts\Channel\Channel::find($channelID);
            $log->addInfo("Sending ping to #pings on The Culture");
            $channel->sendMessage("@everyone " . $message);
        }

        $handle = fopen("/tmp/discord.db", "w+");
        fclose($handle);
        chmod("/tmp/discord.db", 0777);
        $data = null;
        $handle = null;
    }
}
