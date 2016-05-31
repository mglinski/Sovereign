<?php

namespace Sovereign\Plugins\onVoice;

use Discord\Discord;
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

class youtube
{
    public function run(Message $message, Discord $discord, WebSocket $webSocket, Logger $log, &$audioStreams, Channel $channel, cURL $curl)
    {
        $exp = explode(" ", $message->content);
        unset($exp[0]);
        $youtubeLink = implode(" ", $exp);

        // URL Checker
        $parts = parse_url($youtubeLink);
        if (!stristr($parts["host"], "youtube.com")) {
            return $message->reply("Error, you can only use youtube links!");
        }

        // Generate song md5
        $md5 = md5($youtubeLink);
        // Now get the mp3 from
        $songFile = __DIR__ . "/../../../cache/songs/{$md5}.mp3";
        $dl = new YoutubeDl([
            "extract-audio" => true,
            "audio-format" => "mp3",
            "audio-quality" => 0,
            "output" => $songFile
        ]);

        $title = "";
        try {
            $video = $dl->download($youtubeLink);
            $title = $video->getTitle();
            $log->addNotice("Downloading {$title} from YouTube");
        } catch (NotFoundException $e) {
            $log->addError("Error: the song was not found: {$e->getMessage()}");
            $message->reply("Error: the song was not found: {$e->getMessage()}");
        } catch (PrivateVideoException $e) {
            $log->addError("Error: song has been made private: {$e->getMessage()}");
            $message->reply("Error: song has been made private: {$e->getMessage()}");
        } catch (CopyrightException $e) {
            $log->addError("Error: song is under copyright: {$e->getMessage()}");
            $message->reply("Error: song is under copyright: {$e->getMessage()}");
        } catch (\Exception $e) {
            $log->addError("Error: {$e->getMessage()}");
            $message->reply("Error: {$e->getMessage()}");
        }

        $webSocket->joinVoiceChannel($channel)->then(function (VoiceClient $vc) use ($message, $discord, $webSocket, $log, &$audioStreams, $channel, $curl, $songFile, $title) {
            $guildID = $message->getChannelAttribute()->guild_id;

            if (file_exists($songFile)) {
                // Add this audio stream to the array of audio streams
                $audioStreams[$guildID] = $vc;
                $vc->setFrameSize(40)->then(function () use ($vc, &$audioStreams, $guildID, $songFile, $log, $message, $title, $channel) {
                    $vc->setBitrate(128000);
                    $message->reply("Now playing **{$title}** in {$channel->name}");
                    $vc->playFile($songFile, 2)->done(function () use ($vc, &$audioStreams, $guildID) {
                        unset($audioStreams[$guildID]);
                        $vc->close();
                    });
                });
            }
        });
    }
}
