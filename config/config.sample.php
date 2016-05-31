<?php

$config = array();

// Database configuration
$config["db"] = array(
    "dbHost" => "",
    "dbName" => "",
    "dbUser" => "",
    "dbPass" => ""
);

// Discord Token (Look at https://discordapp.com/developers/docs/intro for more info)
$config["bot"] = array(
    "token" => "",
    "prefix" => "%",
    "presence" => "beat the human pinãta"
);

// Cleverbot user/key (Look at https://cleverbot.io/ for more info)
$config["cleverbot"] = array(
    "user" => "",
    "key" => ""
);

// Imgur API Access (Look at https://api.imgur.com/oauth2 for more info)
$config["imgur"] = array(
    "clientID" => "",
    "clientSecret" => ""
);

// Wolfram Alpha (Look at https://developer.wolframalpha.com/portal/apisignup.html for more info)
$config["wolframalpha"] = array(
    "appID" => ""
);

// Permissions, lists the admin(s) discordID and also the various levels available
$config["permissions"] = array(
    "admins" => array(
        118440839776174081
    ),
    "levels" => array(
        "banned", // 0
        "user", // 1
        "guildadmin", // 2
        "admin" // 3
    ),
    "default" => 1 // User
);

return $config;