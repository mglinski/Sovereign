<?php

namespace Sovereign\Plugins;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Sovereign\Sovereign;

class meme {
    public static function onMessage(Message $message, Discord $discord, $config, Sovereign $bot) {
        $memes = array(
            'dank meme',
            '>mfw no gf',
            "m'lady *tip*",
            'le toucan has arrived',
            "jet juel can't melt dank memes",
            '༼ つ ◕_◕ ༽つ gibe',
            'ヽ༼ຈل͜ຈ༽ﾉ raise your dongers ヽ༼ຈل͜ຈ༽ﾉ',
            'ヽʕ •ᴥ•ʔﾉ raise your koalas ヽʕ﻿ •ᴥ•ʔﾉ',
            'ಠ_ಠ',
            '(－‸ლ)',
            '( ͡° ͜ʖ ͡°)',
            '( ° ͜ʖ͡°)╭∩╮',
            '(╯°□°）╯︵ ┻━┻',
            '┬──┬ ノ( ゜-゜ノ)',
            '•_•) ( •_•)>⌐■-■ (⌐■_■)',
            "i dunno lol ¯\\(°_o)/¯",
            "how do i shot web ¯\\(°_o)/¯",
            '(◕‿◕✿)',
            'ヾ(〃^∇^)ﾉ',
            '＼(￣▽￣)／',
            '(ﾉ◕ヮ◕)ﾉ*:･ﾟ✧',
            'ᕕ( ͡° ͜ʖ ͡°)ᕗ',
            'ᕕ( ᐛ )ᕗ ᕕ( ᐛ )ᕗ ᕕ( ᐛ )ᕗ',
            "(ﾉ◕ヮ◕)ﾉ *:･ﾟ✧ SO KAWAII ✧･:* \\(◕ヮ◕\\)",
            'ᕙ༼ຈل͜ຈ༽ᕗ. ʜᴀʀᴅᴇʀ,﻿ ʙᴇᴛᴛᴇʀ, ғᴀsᴛᴇʀ, ᴅᴏɴɢᴇʀ .ᕙ༼ຈل͜ຈ༽ᕗ',
            "(∩ ͡° ͜ʖ ͡°)⊃━☆ﾟ. * ･ ｡ﾟyou've been touched by the donger fairy",
            '(ง ͠° ͟ل͜ ͡°)ง ᴍᴀsᴛᴇʀ ʏᴏᴜʀ ᴅᴏɴɢᴇʀ, ᴍᴀsᴛᴇʀ ᴛʜᴇ ᴇɴᴇᴍʏ (ง ͠° ͟ل͜ ͡°)ง',
            "(⌐■_■)=/̵͇̿̿/'̿'̿̿̿ ̿ ̿̿ ヽ༼ຈل͜ຈ༽ﾉ keep your dongers where i can see them",
            '[̲̅$̲̅(̲̅ ͡° ͜ʖ ͡°̲̅)̲̅$̲̅] do you have change for a donger bill [̲̅$̲̅(̲̅ ͡° ͜ʖ ͡°̲̅)̲̅$̲̅]',
            '╰( ͡° ͜ʖ ͡° )つ──☆*:・ﾟ clickty clack clickty clack with this chant I summon spam to the chat',
            'work it ᕙ༼ຈل͜ຈ༽ᕗ harder make it (ง •̀_•́)ง better do it ᕦ༼ຈل͜ຈ༽ᕤ faster raise ur ヽ༼ຈل͜ຈ༽ﾉ donger',
        );
        $message->reply($memes[array_rand($memes)]);
    }

    public function onStart() {

    }

    public function onTimer() {

    }

    public function information() {
        return (object) array(
            "description" => "Dank memes!",
            "usage" => "",
            "permission" => 1//1 is everyone, 2 is only admin
        );
    }
}