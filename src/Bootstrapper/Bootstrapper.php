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
     * Bootstrapper constructor.
     */
    public function __construct()
    {
        // Load .env file for config
        (Dotenv::createImmutable(__DIR__ . '/../..'))->load();
    }

    /**
     * Boots the application and loads any global dependencies
     *
     * @return void
     */
    public function boot(): void
    {
        // Create Container using PHP-DI
        static::$container = new Container;
        AppFactory::setContainer(self::getContainer());

        // Boot Slim instance
        static::$app = AppFactory::create();

        $this->configure();
    }

    /**
     * Boots smaller app for Doctrine CLI
     *
     * @return EntityManager
     * @throws \Doctrine\ORM\ORMException
     */
    public function cli(): EntityManager
    {
        self::setManager(self::configureDatabase());
        return self::getManager();
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
     * Build dependencies into DI and other services
     */
    public function buildDependencies(): void
    {
        $container = self::getContainer();

        // Doctrine setup
        $manager = self::configureDatabase();
        $container->set('database', fn () => $manager);
        self::setManager($manager);

        $this->buildRoutes();
    }

    /**
     * Configures the database
     *
     * @return EntityManager
     * @throws \Doctrine\ORM\ORMException
     */
    public static function configureDatabase(): EntityManager
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
                'dbname' => getenv('DB_DATABASE'),
                'user' => getenv('DB_USER'),
                'password' => getenv('DB_PASSWORD'),
                'host' => getenv('DB_HOST'),
                'driver' => 'pdo_mysql'
            ];

            return EntityManager::create($conn, $config);
        }
        return self::$manager;
    }

    /**
     * Build routes within Slim and run the app
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
     * EntityManager getter
     *
     * @return EntityManager
     */
    public static function getManager(): EntityManager
    {
        return static::$manager;
    }

    /**
     * EntityManager setter
     */
    public static function setManager(EntityManager $manager): void
    {
        static::$manager = $manager;
    }
}