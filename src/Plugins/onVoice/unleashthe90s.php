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

class unleashthe90s
{
    public function run(Message $message, Discord $discord, WebSocket $webSocket, Logger $log, &$audioStreams, Channel $channel, cURL $curl)
    {
        retry:
        // Get a random song from the 90sbutton playlist
        $playlist = json_decode($curl->get("http://the90sbutton.com/playlist.php"));
        $song = $playlist[array_rand($playlist)];

        // Now get the mp3 from
        $songFile = __DIR__ . "/../../../cache/songs/{$song->youtubeid}.mp3";
        $dl = new YoutubeDl([
            "audio-format" => "mp3",
            "extract-audio" => true,
            "audio-quality" => 0,
            "output" => $songFile
        ]);

        try {
            $log->addNotice("Downloading {$song->title} by {$song->artist}");
            $video = $dl->download("https://www.youtube.com/watch?v={$song->youtubeid}");
        } catch (NotFoundException $e) {
            $log->addError("Error: the song was not found: {$e->getMessage()}");
            goto retry;
        } catch (PrivateVideoException $e) {
            $log->addError("Error: song has been made private: {$e->getMessage()}");
            goto retry;
        } catch (CopyrightException $e) {
            $log->addError("Error: song is under copyright: {$e->getMessage()}");
            goto retry;
        } catch (\Exception $e) {
            $log->addError("Error: {$e->getMessage()}");
            goto retry;
        }

        $webSocket->joinVoiceChannel($channel)->then(function (VoiceClient $vc) use ($message, $discord, $webSocket, $log, &$audioStreams, $channel, $curl, $songFile, $song) {
            $guildID = $message->getChannelAttribute()->guild_id;

            if (file_exists($songFile)) {
                // Add this audio stream to the array of audio streams
                $audioStreams[$guildID] = $vc;
                $vc->setFrameSize(40)->then(function () use ($vc, &$audioStreams, $guildID, $songFile, $log, $message, $song, $channel) {
                    $vc->setBitrate(128000);
                    $message->reply("Now playing **{$song->title}** by **{$song->artist}** in {$channel->name}");
                    $vc->playFile($songFile, 2)->done(function () use ($vc, &$audioStreams, $guildID) {
                        unset($audioStreams[$guildID]);
                        $vc->close();
                    });
                });

            }
        });
    }
}
