<?php
namespace Duppy\Bootstrapper;

use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\ORMException;
use duncan3dc\Sessions\SessionInstance;
use Duppy\Entities\WebUser;
use Duppy\Middleware\CORSMiddleware;
use Hybridauth\Exception\InvalidArgumentException;
use Hybridauth\Hybridauth;
use Ramsey\Uuid\Doctrine\UuidType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Doctrine\DBAL\Types\Type;
use Slim\Factory\AppFactory;
use Dotenv\Dotenv;
use DI\Container;
use Slim\App;

final class Bootstrapper {
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
     * Non-blocking Session Manager
     *
     * @var SessionInstance|null
     */
    public static ?SessionInstance $sessionManager;

    /**
     * Auth handler (Hybridauth)
     *
     * @var Hybridauth|null
     */
    public static ?Hybridauth $authHandler;

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
    public static function boot() {
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
    public static function cli(): EntityManager {
        // Load .env file for config
        (Dotenv::createImmutable(DUPPY_PATH))->load();

        // Database connection
        $manager = self::configureDatabase();
        self::setManager($manager);
        return $manager;
    }

    /**
     * Configures Slim
     *
     * @return void
     */
    public static function configure() {
        $app = self::getApp();
        $app->addRoutingMiddleware();
        $app->addErrorMiddleware(getenv('DUPPY_DEVELOPMENT'), true, true);

        $app->add(new CORSMiddleware);

        self::buildDependencies();
    }

    /**
     * Build dependencies into DI and other services
     */
    public static function buildDependencies() {
        $container = self::getContainer();

        // User settings definitions
        $settingDefinitions = new Settings;
        $settingDefinitions->build();

        // Doctrine setup
        $manager = self::configureDatabase();
        $container->set("database", fn () => $manager);
        self::setManager($manager);

        // Non-blocking session
        $session = new SessionInstance("duppy");
        $container->set("session", fn () => $session);
        self::setSessionManager($session);

        // Hybridauth external login helper
        $hybridauth = self::configureHybridAuth();
        $container->set("authHandler", fn () => $hybridauth);
        self::setAuthHandler($hybridauth);

        ModLoader::build();
        self::buildRoutes();
    }

    /**
     * Configures the database
     *
     * @return EntityManager
     * @throws ORMException|DBALException
     */
    public static function configureDatabase(): EntityManager {
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
     * Configures Hybridauth
     *
     * @return Hybridauth
     * @throws InvalidArgumentException
     */
    public static function configureHybridAuth(): Hybridauth {
        $authSettings = Settings::getSettings([
            "auth.steam.enable", "auth.steam.secret",
            "auth.facebook.enable", "auth.facebook.id", "auth.facebook.secret",
            "auth.google.enable", "auth.google.id", "auth.google.secret",
        ]);

        $config = [
            'callback' => DUPPY_URI,
            'providers' => [
                'Steam' => [
                    'enabled' => $authSettings["auth.steam.enable"],
                    'keys' => [
                        'secret' => $authSettings["auth.steam.secret"],
                    ],
                ],
                'Facebook' => [
                    'enabled' => $authSettings["auth.facebook.enable"],
                    'keys' => [
                        'id' => $authSettings["auth.facebook.id"],
                        'secret' => $authSettings["auth.facebook.secret"],
                    ],
                ],
                'Google' => [
                    'enabled' => $authSettings["auth.google.enable"],
                    'keys' => [
                        'id' => $authSettings["auth.google.id"],
                        'secret' => $authSettings["auth.google.secret"],
                    ],
                ],
            ],
        ];

        /**
         * https://github.com/hybridauth/hybridauth/blob/master/src/Provider/Steam.php
         * https://github.com/hybridauth/hybridauth/blob/master/src/Adapter/OpenID.php
         *
         * $adapter->isConnected();
         */

        return new Hybridauth($config);
    }

    /**
     * Build routes within Slim and run the app
     */
    public static function buildRoutes() {
        self::$router = new Router;
        self::$router->build();

        self::getApp()->run();
    }

    /**
     * App getter
     *
     * @return App
     */
    public static function getApp(): App {
        return static::$app;
    }

    /**
     * Container getter
     *
     * @return Container
     */
    public static function getContainer(): Container {
        return static::$container;
    }

    /**
     * EntityManager getter
     *
     * @return EntityManager
     */
    public static function getManager(): EntityManager {
        return static::$manager;
    }

    /**
     * Convenience function to get a user by their ID
     *
     * @param $id
     * @return WebUser
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function getUser($id): WebUser {
        if ($id == "me") {
            return static::getLoggedInUser();
        }

        $container = static::getContainer();
        $dbo = $container->get("database");
        return $dbo->getRepository("Duppy\Entities\WebUser")->find($id)->first();
    }

    /**
     * Convenience function to get the current logged in user
     *
     * @return WebUser
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function getLoggedInUser(): WebUser {
        $container = static::getContainer();
        $session = $container->get("session");
        $userid = $session->get("user");

        $user = null;
        if ($userid != null) {
            $user = static::getUser($userid);
        }

        return $user;
    }

    /**
     * Session Manager getter
     *
     * @return SessionInstance
     */
    public static function getSessionManager(): SessionInstance {
        return static::$sessionManager;
    }

    /**
     * EntityManager setter
     *
     * @param EntityManager $manager
     */
    public static function setManager(EntityManager $manager) {
        static::$manager = $manager;
    }

    /**
     * EntityManager setter
     *
     * @param EntityManager $manager
     */
    public static function setSessionManager(SessionInstance $manager) {
        static::$sessionManager = $manager;
    }

    /**
     * Auth handler
     *
     * @param Hybridauth $handler
     */
    public static function setAuthHandler(Hybridauth $handler) {
        static::$authHandler = $handler;
    }

}
