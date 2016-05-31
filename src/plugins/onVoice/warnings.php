<?php

namespace Sovereign\Plugins\onVoice;

use Discord\Discord;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Voice\VoiceClient;
use Discord\WebSockets\WebSocket;
use Monolog\Logger;
use Sovereign\Lib\cURL;

class warnings
{
    public function run(Message $message, Discord $discord, WebSocket $webSocket, Logger $log, &$audioStreams, Channel $channel, cURL $curl)
    {
        $webSocket->joinVoiceChannel($channel)->then(function (VoiceClient $vc) use ($message, $discord, $webSocket, $log, &$audioStreams, $channel) {
            $guildID = $message->getChannelAttribute()->guild_id;
            // Add this audio stream to the array of audio streams
            $audioStreams[$guildID] = $vc;
            $vc->setFrameSize(40)->then(function () use ($vc, &$audioStreams, $guildID) {
                $vc->setBitrate(128000);
                $number = mt_rand(1, 6);
                $file = __DIR__ . "/../../../sounds/eve/{$number}.mp3";
                $vc->playFile($file, 2)->done(function () use ($vc, &$audioStreams, $guildID) {
                    unset($audioStreams[$guildID]);
                    $vc->close();
                });
            });
        });
    }
}
