<?php
namespace Sovereign\Service;

use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

class SystemPluginServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    /**
     * The provides array is a way to let the container
     * know that a service is provided by this service
     * provider. Every service that is registered via
     * this service provider must have an alias added
     * to this array or it will be ignored.
     *
     * @var array
     */
    protected $provides = [];

    /**
     * In much the same way, this method has access to the container
     * itself and can interact with it however you wish, the difference
     * is that the boot method is invoked as soon as you register
     * the service provider with the container meaning that everything
     * in this method is eagerly loaded.
     *
     * If you wish to apply inflectors or register further service providers
     * from this one, it must be from a bootable service provider like
     * this one, otherwise they will be ignored.
     */
    public function boot()
    {
        $app = $this->getContainer()->get('app');

        // onMessage plugins
        $app->addPlugin('onMessage', 'about', "\\Sovereign\\Plugins\\onMessage\\about", 1, "Shows information about the bot and it's creator", '', null);
        $app->addPlugin('onMessage', 'auth', "\\Sovereign\\Plugins\\onMessage\\auth", 1, 'Authenticates you against a login with certain restrictions', '<authCode>', null);
        $app->addPlugin('onMessage', 'char', "\\Sovereign\\Plugins\\onMessage\\char", 1, 'Fetches data from EVE-KILL about a character', '<characterName>', null);
        $app->addPlugin('onMessage', 'coinflip', "\\Sovereign\\Plugins\\onMessage\\coinflip", 1, 'Flips a coin, and gives you the results', '', null);
        $app->addPlugin('onMessage', 'config', "\\Sovereign\\Plugins\\onMessage\\config", 1, "Lets you configure parts of the bot, but only if you're admin", '', null);
        $app->addPlugin('onMessage', 'corp', "\\Sovereign\\Plugins\\onMessage\\corp", 1, 'Fetches data from EVE-KILL about a corporation', '<corporationName>', null);
        $app->addPlugin('onMessage', 'eightball', "\\Sovereign\\Plugins\\onMessage\\eightball", 1, 'Shakes the eight ball, and gives you a reply', '', null);
        $app->addPlugin('onMessage', 'eb', "\\Sovereign\\Plugins\\onMessage\\eightball", 1, 'Shakes the eight ball, and gives you a reply', '', null);
        $app->addPlugin('onMessage', 'guilds', "\\Sovereign\\Plugins\\onMessage\\guilds", 1, 'Tells you what guilds (Server) the bot is on', '', null);
        $app->addPlugin('onMessage', 'item', "\\Sovereign\\Plugins\\onMessage\\item", 1, 'Shows you all the information available in the database, for an item', '<itemName>', null);
        $app->addPlugin('onMessage', 'join', "\\Sovereign\\Plugins\\onMessage\\join", 1, 'Tells you the oauth invite link', '', null);
        $app->addPlugin('onMessage', 'meme', "\\Sovereign\\Plugins\\onMessage\\meme", 1, 'Dank memes!', '', null);
        $app->addPlugin('onMessage', 'pc', "\\Sovereign\\Plugins\\onMessage\\pc", 1, 'Lets you check prices of items in EVE. (Global)', '<itemName>', null);
        $app->addPlugin('onMessage', 'amarr', "\\Sovereign\\Plugins\\onMessage\\pc", 1, 'Lets you check prices of items in EVE. (Amarr)', '<itemName>', null);
        $app->addPlugin('onMessage', 'jita', "\\Sovereign\\Plugins\\onMessage\\pc", 1, 'Lets you check prices of items in EVE. (Jita)', '<itemName>', null);
        $app->addPlugin('onMessage', 'dodixie', "\\Sovereign\\Plugins\\onMessage\\pc", 1, 'Lets you check prices of items in EVE. (Dodixie)', '<itemName>', null);
        $app->addPlugin('onMessage', 'hek', "\\Sovereign\\Plugins\\onMessage\\pc", 1, 'Lets you check prices of items in EVE. (Hek)', '<itemName>', null);
        $app->addPlugin('onMessage', 'rens', "\\Sovereign\\Plugins\\onMessage\\pc", 1, 'Lets you check prices of items in EVE. (Rens)', '<itemName>', null);
        $app->addPlugin('onMessage', 'porn', "\\Sovereign\\Plugins\\onMessage\\porn", 1, 'Returns a picture/gif from one of many Imgur categories', '<category>', null);
        $app->addPlugin('onMessage', 'time', "\\Sovereign\\Plugins\\onMessage\\time", 1, 'Tells you the current EVE Time and time in various other timezones', '', null);
        $app->addPlugin('onMessage', 'tq', "\\Sovereign\\Plugins\\onMessage\\tq", 1, 'Tells you the current status of Tranquility', '', null);
        $app->addPlugin('onMessage', 'user', "\\Sovereign\\Plugins\\onMessage\\user", 1, 'Tells you discord information on a user. Including when the bot last saw them, saw them speak, and what they were last playing', '<discordName>', null);
        $app->addPlugin('onMessage', 'wolf', "\\Sovereign\\Plugins\\onMessage\\wolf", 1, 'Asks wolframAlpha a question, and returns the result', '<question>', null);
        $app->addPlugin('onMessage', 'help', "\\Sovereign\\Plugins\\onMessage\\help", 1, 'Shows helpful information about all the plugins available', null, null);
        $app->addPlugin('onMessage', 'memory', "\\Sovereign\\Plugins\\onMessage\\memory", 3, 'Triggers garbage collection', null, null);

        // onTimer plugins
        $app->addPlugin('onTimer', 'memory', "\\Sovereign\\Plugins\\onTimer\\memory", 1, '', '', 1800);
        $app->addPlugin('onTimer', 'jabberPingsTheCulture', "\\Sovereign\\Plugins\\onTimer\\jabberPingsTheCulture", 1, '', '', 5);
        $app->addPlugin('onTimer', 'kills', "\\Sovereign\\Plugins\\onTimer\\kills", 1, '', '', 15);

        // onVoice plugins
        $app->addPlugin('onVoice', 'reapers', "\\Sovereign\\Plugins\\onVoice\\reapers", 1, 'Plays a random quote from Sovereign', '', null);
        $app->addPlugin('onVoice', 'horn', "\\Sovereign\\Plugins\\onVoice\\horn", 1, 'Horns. Just horns..', '', null);
        $app->addPlugin('onVoice', 'pause', "\\Sovereign\\Plugins\\onVoice\\pause", 1, 'Pauses audio playback', '', null);
        $app->addPlugin('onVoice', 'stop', "\\Sovereign\\Plugins\\onVoice\\stop", 1, 'Stops audio playback', '', null);
        $app->addPlugin('onVoice', 'next', "\\Sovereign\\Plugins\\onVoice\\next", 1, 'Goes to the next track if radio90s is playing', '', null);
        $app->addPlugin('onVoice', 'unpause', "\\Sovereign\\Plugins\\onVoice\\unpause", 1, 'Resumes audio playback', '', null);
        $app->addPlugin('onVoice', 'resume', "\\Sovereign\\Plugins\\onVoice\\unpause", 1, 'Resumes audio playback', '', null);
        $app->addPlugin('onVoice', 'warnings', "\\Sovereign\\Plugins\\onVoice\\warnings", 1, 'Plays a random warning sound from EVE-Online', '', null);
        $app->addPlugin('onVoice', 'unleashthe90s', "\\Sovereign\\Plugins\\onVoice\\unleashthe90s", 1, 'Plays a random 90s song', '', null);
        $app->addPlugin('onVoice', 'radio90s', "\\Sovereign\\Plugins\\onVoice\\radio90s", 1, 'Keeps on playing 90s songs, till you go !stop', '', null);
        $app->addPlugin('onVoice', 'radio', "\\Sovereign\\Plugins\\onVoice\\radio", 1, 'Keeps on playing a Radio station, till you go !stop', '', null);
        $app->addPlugin('onVoice', 'youtube', "\\Sovereign\\Plugins\\onVoice\\youtube", 1, 'Plays whatever is linked in the youtube link', '<youtubeLink>', null);
        $app->addPlugin('onVoice', 'yt', "\\Sovereign\\Plugins\\onVoice\\youtube", 1, 'Plays whatever is linked in the youtube link', '<youtubeLink>', null);

        // Attempt to register any configured service providers
        $config = $this->getContainer()->get('config');
        $providers = $config->get('serviceProviders');
        if (!empty($providers)) {
            foreach ($providers as $class) {
                $this->getContainer()->addServiceProvider($class);
            }
        }
    }

    /**
     * This is where the magic happens, within the method you can
     * access the container and register or retrieve anything
     * that you need to, but remember, every alias registered
     * within this method must be declared in the `$provides` array.
     */
    public function register() { }
}
