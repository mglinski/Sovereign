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

class radio
{
    public function run(Message $message, Discord $discord, WebSocket &$webSocket, Logger $log, &$audioStreams, Channel $channel, cURL $curl)
    {
        $explode = explode(" ", $message->content);
        $type = isset($explode[1]) ? $explode[1] : "";
        $url = "";
        switch (strtolower($type)) {
            case "noisefm":
                $url = "http://noisefm.ru:8000/play?icy=http";
                break;
            case "radiobeats":
                $url = "http://streaming.shoutcast.com/RadioBeatsFM";
                break;
            case "vivafm":
                $url = "http://178.32.139.120:8002/stream";
                break;
            case "dance":
                $url = "http://stream.dancewave.online:8080/dance.mp3?icy=http";
                break;
            case "anrdk":
                $url = "http://stream.anr.dk/anr";
                break;
            case "thevoicedk":
                $url = "http://stream.voice.dk/voice128";
                break;
            case "schlager":
                $url = "http://193.34.51.130/schlager_mp3";
                break;
            case "everadio":
                $url = "http://media01.evehost.net:8022/";
                break;
            case "eurodance":
                $url = "http://streaming.radionomy.com/Eurodance-90";
                break;
            default:
                $message->reply("You can listen to the following radios: EVERadio, NoiseFM, RadioBeats, VIVAFM, Dance, ANRDK, TheVoiceDK, EuroDance and Schlager..");
                return;
                break;
        }

        if(!empty($url)) {
            $webSocket->joinVoiceChannel($channel)->then(function (VoiceClient $vc) use ($message, $discord, &$webSocket, $log, &$audioStreams, $channel, $curl, $url) {
                $guildID = $message->getChannelAttribute()->guild_id;

                // Add this audio stream to the array of audio streams
                $audioStreams[$guildID] = $vc;

                // Set the bitrate and framesize
                $vc->setBitrate(128000);
                $vc->setFrameSize(40);

                $params = [
                    'ffmpeg',
                    '-i', $url,
                    '-f', 's16le',
                    '-acodec', 'pcm_s16le',
                    '-loglevel', 0,
                    '-ar', 48000,
                    '-ac', 2,
                    'pipe:1',
                ];

                $audioStreams["eveRadio"][$guildID] = new Process(implode(" ", $params));
                $audioStreams["eveRadio"][$guildID]->start($webSocket->loop);

                $vc->playRawStream($audioStreams["eveRadio"][$guildID]->stdout)->done(function () use (&$audioStreams, $vc, $guildID) {
                    $audioStreams["eveRadio"][$guildID]->close();
                    unset($audioStreams[$guildID]);
                    $vc->close();
                });
            });
        }
    }
}
