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
use Duppy\DuppyException;
use Duppy\DuppyServices\Env;
use Duppy\DuppyServices\ModLoader;
use Duppy\DuppyServices\Settings;
use Duppy\DuppyServices\TokenManager;
use Duppy\Middleware\CORSMiddleware;
use Duppy\Middleware\DuppyServiceMiddleware;
use Duppy\Middleware\EnvironmentMiddleware;
use Duppy\Middleware\RateLimitMiddleware;
use eftec\bladeone\BladeOne;
use Hybridauth\Exception\InvalidArgumentException;
use Hybridauth\Hybridauth;
use JetBrains\PhpStorm\Pure;
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
use PalePurple\RateLimit\Adapter;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Doctrine\UuidType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Doctrine\DBAL\Types\Type;
use RKA\Middleware\IpAddress;
use Slim\Factory\AppFactory;
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
     * Current request
     *
     * @var ServerRequestInterface
     */
    public static ServerRequestInterface $currentRequest;

    /**
     * Boots the application and loads any global dependencies
     *
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     * @throws DuppyException
     */
    public static function boot() {
        // Load .env file for config
        (new Env)->inst()->start();

        // Create Container using PHP-DI
        Bootstrapper::$container = new Container;
        AppFactory::setContainer(Bootstrapper::getContainer());

        // Boot Slim instance
        Bootstrapper::$app = AppFactory::create();

        Bootstrapper::configure();
    }

    /**
     * Boots smaller app for Doctrine CLI
     *
     * @return EntityManager
     * @throws DBALException
     * @throws ORMException
     */
    public static function cli(): EntityManager {
        // Load .env file for config
        (new Env)->inst()->start();

        // Database connection
        return Bootstrapper::configureDatabase();
    }

    /**
     * Slim Testing Application
     *
     * @return App
     * @throws DependencyException
     * @throws NotFoundException
     * @throws DuppyException
     */
    public static function test(): App {
        // Create Container using PHP-DI
        Bootstrapper::$container = new Container;
        AppFactory::setContainer(Bootstrapper::getContainer());

        // Boot Slim instance
        Bootstrapper::$app = AppFactory::create();

        Bootstrapper::configure(true);

        return Bootstrapper::$app;
    }

    /**
     * Configures Slim
     *
     * @param bool $skipDi
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     * @throws DuppyException
     */
    public static function configure(bool $skipDi = false) {
        $app = Bootstrapper::getApp();

        $app->addRoutingMiddleware();
        $app->addErrorMiddleware(Env::G('DUPPY_DEVELOPMENT'), true, true);

        // Default Duppy global middlewares
        $app->add(new IpAddress);
        $app->add(new RateLimitMiddleware);
        $app->add(new DuppyServiceMiddleware);
        $app->add(new CORSMiddleware);
        $app->add(new EnvironmentMiddleware);

        if (!$skipDi) {
            Bootstrapper::buildDependencies();
        }

        // Settings definitions
        (new SettingsBuilder)->build();

        // Mod Loader service
        (new ModLoader)->inst()->build();

        Bootstrapper::buildRoutes();
    }

    /**
     * Build dependencies into DI
     */
    public static function buildDependencies() {
        $container = Bootstrapper::getContainer();

        // Doctrine setup
        $manager = null;
        $container->set("database", fn () => $manager ?? $manager = Bootstrapper::configureDatabase());

        // JSON Web Token (JWS/JWE)
        // todo; please use DI container
        Bootstrapper::configureJWT();

        // Hybridauth external login helper
        $hybridauth = null;
        $container->set("authHandler", fn () => $hybridauth ?? $hybridauth = Bootstrapper::configureHybridAuth());

        // PHPMailer
        $mailer = null;
        $container->set("mailer", fn () => $mailer ?? $mailer = Bootstrapper::configureMailer());

        // OneBlade Templating
        $templateHandler = null;
        $container->set("templateHandler", fn () => $templateHandler ?? $templateHandler = Bootstrapper::configureTemplates());

        // Rate Limit Adapter
        $rateLimitAdapter = null;
        $container->set("rateLimitAdapter", fn () => $rateLimitAdapter ?? $rateLimitAdapter = Bootstrapper::configureRateLimiterAdapter());
    }

    /**
     * Configures the database
     *
     * @return EntityManager
     * @throws DBALException
     * @throws ORMException
     */
    public static function configureDatabase(): EntityManager {
        Type::addType('uuid', UuidType::class);

        $config = Setup::createAnnotationMetadataConfiguration(
            [__DIR__ . '/../'],
            Env::G('DUPPY_DEVELOPMENT'),
            null,
            null,
            false
        );

        // Connection array
        $conn = [
            'dbname' => Env::G('DB_DATABASE'),
            'user' => Env::G('DB_USER'),
            'password' => Env::G('DB_PASSWORD'),
            'host' => Env::G('DB_HOST'),
            'driver' => 'pdo_mysql'
        ];

        return EntityManager::create($conn, $config);
    }

    /**
     * Configures jwt-framework and sets up the Signing and Encryption token builders
     */
    public static function configureJWT() {
        Bootstrapper::$jwsKey = JWKFactory::createFromSecret(Env::G('JWT_SECRET'), [
            'alg' => 'HS256',
            'use' => 'sig',
        ]);

        $encrypt = (new TokenManager)->inst()->isEncryptionEnabled();

        if ($encrypt) {
            Bootstrapper::$jweKey = JWKFactory::createFromSecret(Env::G("JWT_SECRET"), [
                'alg' => 'HS256',
                'use' => 'enc',
            ]);
        }

        $algorithmManager = new AlgorithmManager([
            new HS256(),
        ]);

        Bootstrapper::$jwsBuilder = new JWSBuilder($algorithmManager);
        Bootstrapper::$jwsVerifier = new JWSVerifier($algorithmManager);

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

            Bootstrapper::$jweBuilder = new JWEBuilder($keyEncryptionManager, $contentEncryptionManager, $compressionManager);
            Bootstrapper::$jweDecrypter = new JWEDecrypter($keyEncryptionManager, $contentEncryptionManager, $compressionManager);
        }
    }

    /**
     * Configures Hybridauth
     *
     * @return Hybridauth
     * @throws InvalidArgumentException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws DuppyException
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
        $isDev = Env::G("DUPPY_DEVELOPMENT");
        $mailer = new PHPMailer($isDev);

        $mailer->SMTPDebug = $isDev ? SMTP::DEBUG_SERVER : SMTP::DEBUG_OFF;

        $smtp = Env::G("SMTP");

        if ($smtp) {
            $mailer->isSMTP();
            $mailer->Host = Env::G("SMTP_HOST");
            $mailer->Port = Env::G("SMTP_PORT");

            $user = Env::G("SMTP_USER");

            if (!empty($user)) {
                $mailer->SMTPAuth = true;

                $mailer->Username = $user;
                $mailer->Password = Env::G("SMTP_PASS");
            }

            $cfg = Env::G("SMTP_SECURE");
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

        $mailer->setFrom(Env::G("EMAIL_FROM"));

        return $mailer;
    }

    /**
     * Configure templating engine (BladeOne)
     * This is used for emails
     *
     * @return BladeOne
     */
    public static function configureTemplates(): BladeOne {
        $isDev = Env::G("DUPPY_DEVELOPMENT");
        $pth = DUPPY_PATH . "/templates";

        $views = $pth . "/views";
        $cache = $pth . "/cache";

        return new BladeOne($views, $cache, $isDev ? BladeOne::MODE_DEBUG : BladeOne::MODE_FAST);
    }

    /**
     * @return Adapter
     */
    #[Pure]
    public static function configureRateLimiterAdapter(): Adapter {
        return new Adapter\APC;
    }

    /**
     * Build routes within Slim and run the app
     */
    public static function buildRoutes() {
        Bootstrapper::$router = new Router;
        Bootstrapper::$router->build();

        Bootstrapper::getApp()->run();
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

    /**
     * Current request fetched from DuppyServiceMiddleware
     * This should not be used when possible but is there if needed
     *
     * @return ServerRequestInterface
     */
    public static function getCurrentRequest(): ServerRequestInterface {
        return Bootstrapper::$currentRequest;
    }

    /**
     * @param ServerRequestInterface $request
     */
    public static function setCurrentRequest(ServerRequestInterface $request) {
        Bootstrapper::$currentRequest = $request;
    }

}
