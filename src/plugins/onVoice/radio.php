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

class radio
{
    public function run(Message $message, Discord $discord, WebSocket&$webSocket, Logger $log, &$audioStreams, Channel $channel, cURL $curl)
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
            case "djdeant":
                $url = "http://anycast.dj-deant.com/?icy=http";
                break;
            case "amsterdamtranceradio":
                $url = "http://185.33.21.112:11029/;?icy=http";
                break;
            case "psyradio":
                $url = "http://81.88.36.44:8030/;?icy=http";
                break;
            case "classical":
                $url = "http://109.123.116.202:8020/stream?icy=http";
                break;
            case "classicrock":
                $url = "http://185.33.22.15:11093/;?icy=http";
                break;
            case "groovesalad":
                $url = "http://ice1.somafm.com/groovesalad-128-mp3";
                break;
            case "dronezone":
                $url = "http://ice1.somafm.com/dronezone-128-mp3";
                break;
            case "indiepoprocks":
                $url = "http://ice1.somafm.com/indiepop-128-mp3";
                break;
            case "spacestationsoma":
                $url = "http://ice1.somafm.com/spacestation-128-mp3";
                break;
            case "secretagent":
                $url = "http://ice1.somafm.com/secretagent-128-mp3";
                break;
            case "lush":
                $url = "http://ice1.somafm.com/lush-128-mp3";
                break;
            case "underground80s":
                $url = "http://ice1.somafm.com/u80s-128-mp3";
                break;
            case "deepspaceone":
                $url = "http://ice1.somafm.com/deepspaceone-128-mp3";
                break;
            case "leftcoast70s":
                $url = "http://ice1.somafm.com/seventies-128-mp3";
                break;
            case "bootliquor":
                $url = "http://ice1.somafm.com/bootliquor-128-mp3";
                break;
            case "thetrip":
                $url = "http://ice1.somafm.com/thetrip-128-mp3";
                break;
            case "suburbsofgoa":
                $url = "http://ice1.somafm.com/suburbsofgoa-128-mp3";
                break;
            case "bagelradio":
                $url = "http://ice1.somafm.com/bagel-128-mp3";
                break;
            case "beatblender":
                $url = "http://ice1.somafm.com/beatblender-128-mp3";
                break;
            case "defconradio":
                $url = "http://ice1.somafm.com/defcon-128-mp3";
                break;
            case "sonicuniverse":
                $url = "http://ice1.somafm.com/sonicuniverse-128-mp3";
                break;
            case "folkforward":
                $url = "http://ice1.somafm.com/folkfwd-128-mp3";
                break;
            case "poptron":
                $url = "http://ice1.somafm.com/poptron-128-mp3";
                break;
            case "illinoisstreetlounge":
                $url = "http://ice1.somafm.com/illstreet-128-mp3";
                break;
            case "fluid":
                $url = "http://ice1.somafm.com/fluid-128-mp3";
                break;
            case "thistleradio":
                $url = "http://ice1.somafm.com/thistle-128-mp3";
                break;
            case "seveninchsoul":
                $url = "http://ice1.somafm.com/7soul-128-mp3";
                break;
            case "digitalis":
                $url = "http://ice1.somafm.com/digitalis-128-mp3";
                break;
            case "cliqhopidm":
                $url = "http://ice1.somafm.com/cliqhop-128-mp3";
                break;
            case "missioncontrol":
                $url = "http://ice1.somafm.com/missioncontrol-128-mp3";
                break;
            case "dubstepbeyond":
                $url = "http://ice1.somafm.com/dubstep-128-mp3";
                break;
            case "covers":
                $url = "http://ice1.somafm.com/covers-128-mp3";
                break;
            case "thesilentchannel":
                $url = "http://ice1.somafm.com/silent-128-mp3";
                break;
            case "blackrockfm":
                $url = "http://ice1.somafm.com/brfm-128-mp3";
                break;
            case "doomed":
                $url = "http://ice1.somafm.com/doomed-128-mp3";
                break;
            case "sf1033":
                $url = "http://ice1.somafm.com/sf1033-128-mp3";
                break;
            case "earwaves":
                $url = "http://ice1.somafm.com/earwaves-128-mp3";
                break;
            case "metaldetector":
                $url = "http://ice1.somafm.com/metal-128-mp3";
                break;
            case "90ernedk":
                $url = "http://194.16.21.232/197_dk_aacp";
                break;
            case "novadk":
                $url = "http://stream.novafm.dk/nova128";
                break;
            case "radio100":
                $url = "http://onair.100fmlive.dk/100fm_live.mp3";
                break;
            case "nrjca":
                $url = "http://8743.live.streamtheworld.com/CKMFFMAAC";
                break;
            case "nrjse":
                $url = "http://194.16.21.227/nrj_se_aacp";
                break;
            case "nrjno":
                $url = "http://stream.p4.no/nrj_mp3_mq";
                break;
            case "nrjde":
                $url = "http://95.81.155.20/8032/nrj_145202.mp3";
                break;
            case "limfjorddk":
                $url = "http://media.limfjordnetradio.dk/limfjord128";
                break;
            case "alfadk":
                $url = "http://netradio.radioalfa.dk/";
                break;
            case "partyzonedk":
                $url = "http://stream1.partyzone.nu/mp3";
                break;
            default:
                $message->reply("You can listen to the following radios: EVERadio, 90erneDK, NovaDK, NRJCA, NRJSE, NRJNO, NRJDE, LimfjordDK, AlfaDK, PartyZoneDK, NoiseFM, RadioBeats, VIVAFM, Dance, ANRDK, TheVoiceDK, EuroDance, DJDeanT, AmsterdamTranceRadio, PsyRadio, Classical, ClassicRock, GrooveSalad, DroneZone, IndiePopRocks, SpaceStationSoma, SecretAgent, Lush, Underground80s, DeepSpaceOne, LeftCoast70s, BootLiquor, TheTrip, SuburbsOfGoa, BagelRadio, BeatBlender, DefConRadio, SonicUniverse, FolkForward, PopTron, IllinoisStreetLounge, Fluid, ThistleRadio, SevenInchSoul, Digitalis, CliqhopIDM, MissionControl, DubStepBeyond, Covers, TheSilentChannel, BlackRockFM, Doomed, SF1033, Earwaves, MetalDetector and Schlager..");
                return;
                break;
        }

        if (!empty($url)) {
            $webSocket->joinVoiceChannel($channel)->then(function(VoiceClient $vc) use ($message, $discord, &$webSocket, $log, &$audioStreams, $channel, $curl, $url) {
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
                    '-tune', 'zerolatency',
                    'pipe:1',
                ];

                $audioStreams["eveRadio"][$guildID] = new Process(implode(" ", $params));
                $audioStreams["eveRadio"][$guildID]->start($webSocket->loop);

                $vc->playRawStream($audioStreams["eveRadio"][$guildID]->stdout)->done(function() use (&$audioStreams, $vc, $guildID) {
                    $audioStreams["eveRadio"][$guildID]->close();
                    unset($audioStreams[$guildID]);
                    $vc->close();
                });
            });
        }
    }
}
