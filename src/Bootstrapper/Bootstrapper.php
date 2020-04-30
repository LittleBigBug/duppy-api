<?php
namespace Duppy\Bootstrapper;

use Slim\Factory\AppFactory;
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
     * Boots the application and loads any global dependencies
     *
     * @return void
     */
    public function boot(): void
    {
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
        static::$app->addRoutingMiddleware();

        // Make sure to update DUPPY_DEVELOPMENT in .env file when we move into prod
        static::$app->addErrorMiddleware(getenv('DUPPY_DEVELOPMENT'), true, true);
        $this->buildRoutes();
    }

    public function buildRoutes(): void
    {
        static::$app->run();
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
}