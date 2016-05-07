<?php

namespace Sovereign\Plugins;

use DateTime;
use DateTimeZone;
use Discord\Discord;
use Discord\Parts\Channel\Message;
use Sovereign\Sovereign;

class time {
    public static function onMessage(Message $message, Discord $discord, $config, Sovereign $bot) {
        $date = date("d-m-Y");
        $fullDate = date("Y-m-d H:i:s");
        $dateTime = new DateTime($fullDate);
        $et = $dateTime->setTimezone(new DateTimeZone("America/New_York"));
        $et = $et->format("H:i:s");
        $pt = $dateTime->setTimezone(new DateTimeZone("America/Los_Angeles"));
        $pt = $pt->format("H:i:s");
        $utc = $dateTime->setTimezone(new DateTimeZone("UTC"));
        $utc = $utc->format("H:i:s");
        $cet = $dateTime->setTimezone(new DateTimeZone("Europe/Copenhagen"));
        $cet = $cet->format("H:i:s");
        $msk = $dateTime->setTimezone(new DateTimeZone("Europe/Moscow"));
        $msk = $msk->format("H:i:s");
        $aest = $dateTime->setTimezone(new DateTimeZone("Australia/Sydney"));
        $aest = $aest->format("H:i:s");
        $msg = "**Current EVE Time:** {$utc} / **EVE Date:** {$date} / **PT:** {$pt} / **ET:** {$et} / **CET:** {$cet} / **MSK:** {$msk} / **AEST:** {$aest}";
        $message->reply($msg);
    }

    public function onStart() {

    }

    public function onTimer() {

    }

    public function information() {
        return (object) array(
            "description" => "Tells you the current EVE Time and time in various other timezones",
            "usage" => "",
            "permission" => 1//1 is everyone, 2 is only admin
        );
    }
}