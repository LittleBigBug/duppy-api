<?php
namespace Duppy\Bootstrapper;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Slim\Factory\AppFactory;
use Dotenv\Dotenv;
use DI\Container;
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
     * Container instance
     *
     * @var Container|null
     */
    public static Container $container;

    /**
     * Doctrine entity manager instance
     *
     * @var EntityManager|null
     */
    public static EntityManager $manager;

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

    /**
     * Build dependencies into DI
     */
    public function buildDependencies(): void
    {
        //self::getContainer()->set('database', fn () => $this->configureDatabase());
        self::configureDatabase();

        $this->buildRoutes();
    }

    public static function configureDatabase(): void
    {
        if (!isset(self::$manager)) {
            // Enable doctrine annotations
            $config = Setup::createAnnotationMetadataConfiguration(
                [__DIR__ . '/../'],
                getenv('DUPPY_DEVELOPMENT'),
                null,
                null,
                false
            );

            // Connection array
            $conn = [
                'dbname' => getenv('DB_HOST'),
                'user' => getenv('DB_USER'),
                'password' => getenv('DB_PASSWORD'),
                'host' => getenv('DB_HOST'),
                'driver' => 'pdo_mysql'
            ];

            self::$manager = EntityManager::create($conn, $config);
        }
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
     * @return Container
     */
    public static function getContainer(): Container
    {
        return static::$container;
    }

    /**
     * Container getter
     *
     * @return EntityManager
     */
    public static function getManager(): EntityManager
    {
        return static::$manager;
    }
}