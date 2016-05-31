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

class radio90s
{
    public function run(Message $message, Discord $discord, WebSocket $webSocket, Logger $log, &$audioStreams, Channel $channel, cURL $curl)
    {
        $webSocket->joinVoiceChannel($channel)->then(function (VoiceClient $vc) use ($message, $discord, $webSocket, $log, &$audioStreams, $channel, $curl) {
            $guildID = $message->getChannelAttribute()->guild_id;

            // Add this audio stream to the array of audio streams
            $audioStreams[$guildID] = $vc;

            // Set the bitrate to 128Kbit
            $vc->setBitrate(128000);
            $vc->setFrameSize(40);

            $tickQueue = function () use (&$tickQueue, &$vc, &$message, &$channel, &$curl, &$log, &$audioStreams, $guildID) {
                // Get the song we'll be playing this round
                $data = $this->getSong($curl, $log);
                $song = $data["songData"];
                $songFile = $data["songFile"];

                // Do we really want it to spam the origin channel with what song we're playing all the time?
                //$message->getChannelAttribute()->sendMessage("Now playing **{$song->title}** by **{$song->artist}** in {$channel->name}");
                $log->addInfo("Now playing **{$song->title}** by **{$song->artist}** in {$channel->name}");
                $vc->playFile($songFile)->done(function () use (&$tickQueue, $vc, &$log, &$audioStreams, $guildID) {
                    if (isset($audioStreams[$guildID])) {
                        $log->addInfo("Going to next song..");
                        $vc->stop();
                        $tickQueue();
                    }
                });
            };

            if (isset($audioStreams[$guildID])) {
                $tickQueue();
            }
        });
    }

    private function getSong(cURL $curl, Logger $log)
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
            $dl->download("https://www.youtube.com/watch?v={$song->youtubeid}");
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

        if (file_exists($songFile)) {
            return array("songFile" => $songFile, "songData" => $song);
        } else {
            goto retry;
        }
    }
}
