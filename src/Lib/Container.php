<?php namespace Sovereign\Lib;

use League\Container\Container;
use League\Container\ReflectionContainer;
use Sovereign\Lib\Pattern\Singleton;
use Sovereign\Service\SystemServiceProvider;

class ContainerSingleton extends Singleton {

    /**
     * @var Container
     */
    private $container;

    /**
     * @return Container
     */
    public function getContainerInstance() {

        if(null === $this->container) {
            $this->container = new Container;

            //. Attempt to autowire class constructor dependencies
            $this->container->delegate(
                new ReflectionContainer
            );

            // register default config file
            $this->container->add('configFile', CONFIG_FILE);

            // Add the default system service provider
            $this->container->addServiceProvider(SystemServiceProvider::class);
        }

        return $this->container;
    }
}
