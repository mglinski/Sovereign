<?php

use League\Container\Container;
use League\Container\ReflectionContainer;

if (! function_exists('getContainer')) {
    // Initialize and save the container instance
    function getContainer() {
        static $container;
        if(!isset($container)) {
            $container = new Container;

            //. Attempt to autowire class constructor dependencies
            $container->delegate(
                new ReflectionContainer
            );


            // register default config file
            $container->add('configFile', BASEDIR . '/config/config.php');

            // Add the default system service provider
            $container->addServiceProvider(\Sovereign\Service\SystemServiceProvider::class);

            // Load the bot into the service container
            $container->share('app', \Sovereign\Sovereign::class)->withArgument($container);

        }

        return $container;
    }
}
