<?php
namespace Sovereign\Service;

use League\Container\ServiceProvider\AbstractServiceProvider;

class SystemServiceProvider extends AbstractServiceProvider
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
    protected $provides = [
        'log',
        'db',
        'config',
        'curl',
        'settings',
        'permissions',
        'serverConfig',
        'users',
    ];

    /**
     * This is where the magic happens, within the method you can
     * access the container and register or retrieve anything
     * that you need to, but remember, every alias registered
     * within this method must be declared in the `$provides` array.
     */
    public function register()
    {
        $container = $this->getContainer();

        $container->share('log', 'Monolog\Logger')->withArgument('Sovereign');
        $container->get('log')->pushHandler(new \Monolog\Handler\StreamHandler('php://stdout', \Monolog\Logger::INFO));

        $container->share('db', 'Sovereign\Lib\Db')->withArgument('config')->withArgument('log')->withArgument($container);
        $container->share('config', 'Sovereign\Lib\Config')->withArgument('configFile')->withArgument('log');
        $container->share('curl', 'Sovereign\Lib\cURL')->withArgument('log');
        $container->share('settings', 'Sovereign\Lib\Settings')->withArgument('db');
        $container->share('permissions', 'Sovereign\Lib\Permissions')->withArgument('db')->withArgument('config');
        $container->share('serverConfig', 'Sovereign\Lib\ServerConfig')->withArgument('db');
        $container->share('users', 'Sovereign\Lib\Users')->withArgument('db');
    }
}
