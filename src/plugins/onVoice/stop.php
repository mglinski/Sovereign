<?php

namespace Sovereign\Plugins\onVoice;

use Discord\Discord;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\WebSockets\WebSocket;
use Monolog\Logger;
use Sovereign\Lib\cURL;

class stop
{
    public function run(Message $message, Discord $discord, WebSocket $webSocket, Logger $log, &$audioStreams, Channel $channel, cURL $curl)
    {
        $guildID = $channel->guild_id;
        if (isset($audioStreams[$guildID])) {
            // Kill the EVERadio FFMPEG stream if it's running
            if (isset($audioStreams["eveRadio"][$guildID])) {
                $audioStreams["eveRadio"][$guildID]->close();
            }

            $audioStreams[$guildID]->stop();
            $audioStreams[$guildID]->close();
            unset($audioStreams[$guildID]);
            $message->reply("Stopping audio playback..");
        }
    }
}
