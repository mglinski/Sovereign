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
        }

        return $container;
    }
}
