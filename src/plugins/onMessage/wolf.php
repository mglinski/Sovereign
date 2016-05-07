<?php

namespace Sovereign\Plugins;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Sovereign\Sovereign;

class wolf {
    public static function onMessage(Message $message, Discord $discord, $config, Sovereign $bot) {
        $container = $bot->getContainer();
        $explode = explode(" ", $message->content);
        unset($explode[0]);
        $query = implode(" ", $explode);

        /** @var \WolframAlpha\QueryResult $result */
        $result = $container["wolframAlpha"]->process($query, array(), array("image", "plaintext"));
        /** @var \WolframAlpha\Pod $pod */
        $pod = $result->pods["Result"];

        if(!empty($pod)) {
            /** @var \WolframAlpha\Subpod $subPod */
            $subPod = $pod->subpods[0];

            if(strlen($subPod->img->src) > 0)
                $message->reply("Result: {$subPod->plaintext}\r\n {$subPod->img->src}");
            else
                $message->reply("Result: {$subPod->plaintext}");
        } else {
            $message->reply("WolframAlpha did not have an answer to your query..");
        }

    }

    public function onStart() {

    }

    public function onTimer() {

    }

    public function information() {
        return (object) array(
            "description" => "Asks wolframAlpha a question, and returns the result",
            "usage" => "<question>",
            "permission" => 1//1 is everyone, 2 is only admin
        );
    }
}