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
    public App $app;

    /**
     * Boots the application and loads any global dependencies
     *
     * @return void
     */
    public function boot(): void
    {
        // Create the slim instance
        $this->app = AppFactory::create();

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
        $this->app->addRoutingMiddleware();

        // Make sure to update DUPPY_DEVELOPMENT in .env file when we move into prod
        $this->app->addErrorMiddleware(getenv('DUPPY_DEVELOPMENT'), true, true);
        $this->buildRoutes();
    }

    public function buildRoutes(): void
    {
        $this->app->run();
    }
}