<?php

namespace Sovereign\Plugins;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Pimple\Container;
use Sovereign\Sovereign;

class memory {
    public static function onTimer(Discord $discord, $container, $config) {
        $container["log"]->addInfo("Memory in use before garbage collection: " . memory_get_usage() / 1024 / 1024 . "MB");
        //gc_collect_cycles();
        $container["log"]->addInfo("Memory in use after garbage collection: " . memory_get_usage() / 1024 / 1024 . "MB");
    }
}
