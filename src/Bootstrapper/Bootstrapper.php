<?php
namespace Duppy\Bootstrapper;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\ORMException;
use Duppy\Middleware\SteamMiddleware;
use Ramsey\Uuid\Doctrine\UuidType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Doctrine\DBAL\Types\Type;
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
    public static ?App $app;

    /**
     * Container instance
     *
     * @var Container|null
     */
    public static ?Container $container;

    /**
     * Doctrine entity manager instance
     *
     * @var EntityManager|null
     */
    public static ?EntityManager $manager;

    /**
     * Duppy Router instance
     *
     * @var Router|null
     */
    public static ?Router $router;

    /**
     * Boots the application and loads any global dependencies
     *
     * @return void
     */
    public static function boot(): void
    {
        // Load .env file for config
        (Dotenv::createImmutable(DUPPY_PATH))->load();

        // Create Container using PHP-DI
        self::$container = new Container;
        AppFactory::setContainer(self::getContainer());

        // Boot Slim instance
        self::$app = AppFactory::create();

        self::configure();
    }

    /**
     * Boots smaller app for Doctrine CLI
     *
     * @return EntityManager
     * @throws ORMException|DBALException
     */
    public static function cli(): EntityManager
    {
        self::setManager(self::configureDatabase());
        return self::getManager();
    }

    /**
     * Configures Slim
     *
     * @return void
     */
    public static function configure(): void
    {
        $app = self::getApp();
        $app->addRoutingMiddleware();
        $app->addErrorMiddleware(getenv('DUPPY_DEVELOPMENT'), true, true);

        $app->add(new SteamMiddleware);

        self::buildDependencies();
    }

    /**
     * Build dependencies into DI and other services
     */
    public static function buildDependencies(): void
    {
        $container = self::getContainer();

        // Doctrine setup
        $manager = self::configureDatabase();
        $container->set('database', fn () => $manager);
        self::setManager($manager);

        ModLoader::build();
        self::buildRoutes();
    }

    /**
     * Configures the database
     *
     * @return EntityManager
     * @throws ORMException|DBALException
     */
    public static function configureDatabase(): EntityManager
    {
        Type::addType('uuid', UuidType::class);

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
    public static function buildRoutes(): void
    {
        self::$router = new Router;
        self::$router->build();

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
     *
     * @param EntityManager $manager
     */
    public static function setManager(EntityManager $manager): void
    {
        static::$manager = $manager;
    }
}