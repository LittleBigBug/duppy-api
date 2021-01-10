<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 *                        Main App Bootstrapper
 */

namespace Duppy\Bootstrapper;

use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\ORMException;
use Duppy\Builders\Router;
use Duppy\Builders\SettingsBuilder;
use Duppy\DuppyServices\Env;
use Duppy\DuppyServices\ModLoader;
use Duppy\DuppyServices\Settings;
use Duppy\DuppyServices\TokenManager;
use Duppy\Middleware\CORSMiddleware;
use eftec\bladeone\BladeOne;
use Hybridauth\Exception\InvalidArgumentException;
use Hybridauth\Hybridauth;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\Encryption\Algorithm\ContentEncryption\A128CBCHS256;
use Jose\Component\Encryption\Algorithm\KeyEncryption\A256KW;
use Jose\Component\Encryption\Compression\CompressionMethodManager;
use Jose\Component\Encryption\Compression\Deflate;
use Jose\Component\Encryption\JWEBuilder;
use Jose\Component\Encryption\JWEDecrypter;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\HS256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\JWSVerifier;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
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
     * JWS Key
     *
     * @var JWK|null
     */
    public static ?JWK $jwsKey;

    /**
     * JWE Key
     *
     * @var JWK|null
     */
    public static ?JWK $jweKey;

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
     * Duppy Router Builder instance
     *
     * @var Router|null
     */
    public static ?Router $router;

    /**
     * Boots the application and loads any global dependencies
     *
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function boot() {
        // Load .env file for config
        (new Env)->inst()->start();

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
        return self::configureDatabase();
    }

    /**
     * Slim Testing Application
     *
     * @return App
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function test(): App {
        // Create Container using PHP-DI
        self::$container = new Container;
        AppFactory::setContainer(self::getContainer());

        // Boot Slim instance
        self::$app = AppFactory::create();

        self::configure(true);

        return self::$app;
    }

    /**
     * Configures Slim
     *
     * @param bool $skipDi
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function configure(bool $skipDi = false) {
        $app = self::getApp();
        $app->addRoutingMiddleware();
        $app->addErrorMiddleware(getenv('DUPPY_DEVELOPMENT'), true, true);

        $app->add(new CORSMiddleware);

        if (!$skipDi) {
            self::buildDependencies();
        }

        // Settings definitions
        (new SettingsBuilder)->build();

        // Mod Loader service
        (new ModLoader)->inst()->build();

        self::buildRoutes();
    }

    /**
     * Build dependencies into DI
     */
    public static function buildDependencies() {
        $container = self::getContainer();

        // Doctrine setup
        $manager = null;
        $container->set("database", fn () => $manager ?? $manager = self::configureDatabase());

        // JSON Web Token (JWS/JWE)
        // todo; please use DI container
        Bootstrapper::configureJWT();

        // Hybridauth external login helper
        $hybridauth = null;
        $container->set("authHandler", fn () => $hybridauth ?? $hybridauth = self::configureHybridAuth());

        // PHPMailer
        $mailer = null;
        $container->set("mailer", fn () => $mailer ?? $mailer = self::configureMailer());

        // OneBlade Templating
        $templateHandler = null;
        $container->set("templateHandler", fn () => $templateHandler ?? $templateHandler = self::configureTemplates());
    }

    /**
     * Configures the database
     *
     * @return EntityManager
     * @throws ORMException|DBALException
     */
    public static function configureDatabase(): EntityManager {
        Type::addType('uuid', UuidType::class);

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

    /**
     * Configures jwt-framework and sets up the Signing and Encryption token builders
     */
    public static function configureJWT() {
        self::$jwsKey = JWKFactory::createFromSecret(getenv('JWT_SECRET'), [
            'alg' => 'HS256',
            'use' => 'sig',
        ]);

        $encrypt = (new TokenManager)->inst()->isEncryptionEnabled();

        if ($encrypt) {
            self::$jweKey = JWKFactory::createFromSecret(getenv("JWT_SECRET"), [
                'alg' => 'HS256',
                'use' => 'enc',
            ]);
        }

        $algorithmManager = new AlgorithmManager([
            new HS256(),
        ]);

        self::$jwsBuilder = new JWSBuilder($algorithmManager);
        self::$jwsVerifier = new JWSVerifier($algorithmManager);

        if ($encrypt) {
            $keyEncryptionManager = new AlgorithmManager([
                new A256KW(),
            ]);

            $contentEncryptionManager = new AlgorithmManager([
                new A128CBCHS256(),
            ]);

            $compressionManager = new CompressionMethodManager([
                new Deflate(),
            ]);

            self::$jweBuilder = new JWEBuilder($keyEncryptionManager, $contentEncryptionManager, $compressionManager);
            self::$jweDecrypter = new JWEDecrypter($keyEncryptionManager, $contentEncryptionManager, $compressionManager);
        }
    }

    /**
     * Configures Hybridauth
     *
     * @return Hybridauth
     * @throws InvalidArgumentException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function configureHybridAuth(): Hybridauth {
        $authSettings = (new Settings)->inst()->getSettings([
            "auth.steam.enable", "auth.steam.secret",
            "auth.facebook.enable", "auth.facebook.id", "auth.facebook.secret",
            "auth.google.enable", "auth.google.id", "auth.google.secret",
        ]);

        $url = strtok(DUPPY_FULL_URL, "?");

        $config = [
            'callback' => $url,
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

        return new Hybridauth($config);
    }

    /**
     * Configure PHPMailer
     * @throws PHPMailerException
     */
    public static function configureMailer(): PHPMailer {
        $isDev = getenv("DUPPY_DEVELOPMENT");
        $mailer = new PHPMailer($isDev);

        $mailer->SMTPDebug = $isDev ? SMTP::DEBUG_SERVER : SMTP::DEBUG_OFF;

        $smtp = getenv("SMTP");

        if ($smtp) {
            $mailer->isSMTP();
            $mailer->Host = getenv("SMTP_HOST");
            $mailer->Port = getenv("SMTP_PORT");

            $user = getenv("SMTP_USER");

            if (!empty($user)) {
                $mailer->SMTPAuth = true;

                $mailer->Username = $user;
                $mailer->Password = getenv("SMTP_PASS");
            }

            $cfg = getenv("SMTP_SECURE");
            $protocol = null;

            switch ($cfg) {
                case "tls":
                    $protocol = PHPMailer::ENCRYPTION_STARTTLS;
                    break;
                case "ssl":
                    $protocol = PHPMailer::ENCRYPTION_SMTPS;
                    break;
                default:
                    break;
            }

            if (!empty($protocol)) {
                $mailer->SMTPSecure = $protocol;
            }
        }

        $mailer->setFrom(getenv("EMAIL_FROM"));

        return $mailer;
    }

    /**
     * Configure templating engine (BladeOne)
     * This is used for emails
     *
     * @return BladeOne
     */
    public static function configureTemplates(): BladeOne {
        $isDev = getenv("DUPPY_DEVELOPMENT");
        $pth = DUPPY_PATH . "/templates";

        $views = $pth . "/views";
        $cache = $pth . "/cache";

        return new BladeOne($views, $cache, $isDev ? BladeOne::MODE_DEBUG : BladeOne::MODE_FAST);
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
        return Bootstrapper::$app;
    }

    /**
     * Container getter
     *
     * @return Container
     */
    public static function getContainer(): Container {
        return Bootstrapper::$container;
    }

    /**
     * JWSKey getter
     *
     * @return JWK
     */
    public static function getJWSKey(): JWK {
        return Bootstrapper::$jwsKey;
    }

    /**
     * JWEKey getter
     *
     * @return JWK
     */
    public static function getJWEKey(): JWK {
        return Bootstrapper::$jweKey;
    }

    /**
     * JWS Builder getter
     *
     * @return JWSBuilder
     */
    public static function getJWSBuilder(): JWSBuilder {
        return Bootstrapper::$jwsBuilder;
    }

    /**
     * JWS Verifier getter
     *
     * @return JWSVerifier
     */
    public static function getJWSVerifier(): JWSVerifier {
        return Bootstrapper::$jwsVerifier;
    }

    /**
     * JWE Builder getter
     *
     * @return JWEBuilder
     */
    public static function getJWEBuilder(): JWEBuilder {
        return Bootstrapper::$jweBuilder;
    }

    /**
     * JWE Decrypter getter
     *
     * @return JWEDecrypter
     */
    public static function getJWEDecrypter(): JWEDecrypter {
        return Bootstrapper::$jweDecrypter;
    }

}
