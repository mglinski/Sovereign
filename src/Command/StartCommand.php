<?php
namespace Sovereign\Command;

use Sovereign\Service\SystemPluginServiceProvider;
use Sovereign\Sovereign;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StartCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('start')
            ->setDescription('Launch the sovereign bot')
            ->addOption(
                'configFile',
                null,
                InputOption::VALUE_OPTIONAL,
                'The config php file to load',
                'config/config.php'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Use all the memory
        ini_set('memory_limit', '-1');
        gc_enable();
        error_reporting(1);
        error_reporting(E_ALL);
        define('DISCORDPHP_STARTTIME', microtime(true));
        define('BASEDIR', __DIR__);
        define('SOVEREIGN_CONFIG_FILE',  realpath('./' . ltrim($input->getOption('configFile'), '/')));

        // Init the container object
        $container = getContainer();

        // Load the bot into the service container
        $container->share('app', Sovereign::class)->withArgument($container);

        try {
            // Init the bot
            $container->get('log')->addInfo('Sovereign is starting up..');
            $bot = $container->get('app');

            // Register the default plugins and services, and boot them.
            $container->addServiceProvider(SystemPluginServiceProvider::class);
        } catch (\Exception $e) {
            $container->get('log')->addError('An error occurred: ' . $e->getMessage());
            die();
        }

        // Launch the bot
        $bot->run();
    }
}

