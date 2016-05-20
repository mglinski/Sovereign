<?php

namespace Sovereign\Plugins\onVoice;

use Discord\Discord;
use Discord\Helpers\Process;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Voice\VoiceClient;
use Discord\WebSockets\WebSocket;
use Monolog\Logger;
use Sovereign\Lib\cURL;
use YoutubeDl\Exception\CopyrightException;
use YoutubeDl\Exception\NotFoundException;
use YoutubeDl\Exception\PrivateVideoException;
use YoutubeDl\YoutubeDl;

class everadio
{
    public function run(Message $message, Discord $discord, WebSocket &$webSocket, Logger $log, &$audioStreams, Channel $channel, cURL $curl)
    {
        $webSocket->joinVoiceChannel($channel)->then(function (VoiceClient $vc) use ($message, $discord, &$webSocket, $log, &$audioStreams, $channel, $curl) {
            $guildID = $message->getChannelAttribute()->guild_id;

            // Add this audio stream to the array of audio streams
            $audioStreams[$guildID] = $vc;

            // Set the bitrate and framesize
            $vc->setBitrate(128);
            $vc->setFrameSize(20);

            $songURL = 'http://media01.evehost.net:8022/';
            $params = [
                'ffmpeg',
                '-i', $songURL,
                '-f', 's16le',
                '-acodec', 'pcm_s16le',
                '-loglevel', 0,
                '-ar', 48000,
                '-ac', 2,
                'pipe:1',
            ];
            
            $audioStreams["eveRadio"][$guildID] = new Process(implode(" ", $params));
            $audioStreams["eveRadio"][$guildID]->start($webSocket->loop);

            $message->getChannelAttribute()->sendMessage("Now playing EVE-Radio (Livestream) in {$channel->name}");
            $vc->playRawStream($audioStreams["eveRadio"][$guildID]->stdout)->done(function() use (&$audioStreams, $vc, $guildID) {
                $audioStreams["eveRadio"][$guildID]->close();
                unset($audioStreams[$guildID]);
                $vc->close();
            });
        });
    }
}
