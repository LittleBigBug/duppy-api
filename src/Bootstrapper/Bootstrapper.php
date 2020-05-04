<?php
namespace Duppy\Bootstrapper;

use Slim\Factory\AppFactory;
use DI\Container;
use Dotenv\Dotenv;
use Slim\App;

final class Bootstrapper
{
    /**
     * Slim instance
     *
     * @var App|null
     */
    public static App $app;

    /**
     * Slim instance
     *
     * @var App|null
     */
    public static Container $container;

    /**
     * Boots the application and loads any global dependencies
     *
     * @return void
     */
    public function boot(): void
    {
        // Create Container using PHP-DI
        static::$container = new Container;

        // Set container for app
        AppFactory::setContainer(self::getContainer());

        // Create the slim instance
        static::$app = AppFactory::create();

        // Load our .env file for configuration
        (Dotenv::createImmutable(__DIR__ . '/../..'))->load();
        $this->configure();
    }

    /**
     * Configures Slim
     *
     * @return void
     */
    public function configure(): void
    {
        $app = self::getApp();
        $app->addRoutingMiddleware();
        $app->addErrorMiddleware(getenv('DUPPY_DEVELOPMENT'), true, true);

        $this->buildDependencies();
    }

    public function buildDependencies(): void
    {
        $this->buildRoutes();
    }

    /**
     * Build routes within Slim
     */
    public function buildRoutes(): void
    {
        (new Router)->build();
        self::getApp()->run();
    }

    /**
     * App getter
     *
     * @return App
     */
    public static function getApp(): App
    {
        return static::$app;
    }

    /**
     * Container getter
     *
     * @return App
     */
    public static function getContainer(): Container
    {
        return static::$container;
    }
}