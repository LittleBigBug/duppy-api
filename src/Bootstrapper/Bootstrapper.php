<?php
namespace Duppy\Bootstrapper;

use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\ORMException;
use Duppy\Middleware\CORSMiddleware;
use Hybridauth\Exception\InvalidArgumentException;
use Hybridauth\Hybridauth;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\Encryption\Algorithm\ContentEncryption\A256CBCHS512;
use Jose\Component\Encryption\Algorithm\KeyEncryption\A256GCMKW;
use Jose\Component\Encryption\Compression\CompressionMethodManager;
use Jose\Component\Encryption\Compression\Deflate;
use Jose\Component\Encryption\JWEBuilder;
use Jose\Component\Encryption\JWEDecrypter;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\ES256;
use Jose\Component\Signature\Algorithm\PS256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\JWSVerifier;
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
     * JWT Key
     *
     * @var JWK|null
     */
    public static ?JWK $jwKey;

    /**
     * JWS (Signed) token builder
     *
     * @var JWSBuilder|null
     */
    public static ?JWSBuilder $jwsBuilder;

    /**
     * JWS Verifier
     *
     * @var JWSVerifier|null
     */
    public static ?JWSVerifier $jwsVerifier;

    /**
     * JWE (Encrypted) token builder
     *
     * @var JWEBuilder|null
     */
    public static ?JWEBuilder $jweBuilder;

    /**
     * JWE Decrypter
     *
     * @var JWEDecrypter|null
     */
    public static ?JWEDecrypter $jweDecrypter;

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

        // JSON Web Token (JWS/JWE)
        static::configureJWT();

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
     * Configures jwt-framework and sets up the Signing and Encryption token builders
     */
    public static function configureJWT() {
        self::$jwKey = JWKFactory::createFromSecret(getenv('JWT_SECRET'), [
            'alg' => 'HS256',
        ]);

        $algorithmManager = new AlgorithmManager([
            new ES256(),
            new PS256(),
        ]);

        self::$jwsBuilder = new JWSBuilder($algorithmManager);
        self::$jwsVerifier = new JWSVerifier($algorithmManager);

        $keyEncryptionManager = new AlgorithmManager([
            new A256GCMKW(),
        ]);

        $contentEncryptionManager = new AlgorithmManager([
            new A256CBCHS512(),
        ]);

        $compressionManager = new CompressionMethodManager([
            new Deflate(),
        ]);

        self::$jweBuilder = new JWEBuilder($keyEncryptionManager, $contentEncryptionManager, $compressionManager);
        self::$jweDecrypter = new JWEDecrypter($keyEncryptionManager, $contentEncryptionManager, $compressionManager);
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
     * JWKey getter
     *
     * @return JWK
     */
    public static function getJWKey(): JWK {
        return static::$jwKey;
    }

    /**
     * JWS Builder getter
     *
     * @return JWSBuilder
     */
    public static function getJWSBuilder(): JWSBuilder {
        return static::$jwsBuilder;
    }

    /**
     * JWS Verifier getter
     *
     * @return JWSVerifier
     */
    public static function getJWSVerifier(): JWSVerifier {
        return static::$jwsVerifier;
    }

    /**
     * JWE Builder getter
     *
     * @return JWEBuilder
     */
    public static function getJWEBuilder(): JWEBuilder {
        return static::$jweBuilder;
    }

    /**
     * JWE Decrypter getter
     *
     * @return JWEDecrypter
     */
    public static function getJWEDecrypter(): JWEDecrypter {
        return static::$jweDecrypter;
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
     * Auth handler
     *
     * @param Hybridauth $handler
     */
    public static function setAuthHandler(Hybridauth $handler) {
        static::$authHandler = $handler;
    }

}
