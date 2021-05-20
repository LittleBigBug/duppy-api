<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 *                        Main App Bootstrapper
 */

namespace Duppy\Bootstrapper;

use Duppy\Middleware\RouteRateLimitMiddleware;
use Memcached;
use Slim\App;
use Slim\Factory\AppFactory;
use DI\Container;
use DI\NotFoundException;
use DI\DependencyException;
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
use Middlewares\Whoops;
use eftec\bladeone\BladeOne;
use PalePurple\RateLimit\Adapter;
use JetBrains\PhpStorm\Pure;
use Hybridauth\Hybridauth;
use Hybridauth\Exception\InvalidArgumentException;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\Types\Type;
use RKA\Middleware\IpAddress;
use Ramsey\Uuid\Doctrine\UuidType;
use Psr\Http\Message\ServerRequestInterface;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Encryption\JWEBuilder;
use Jose\Component\Encryption\JWEDecrypter;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Signature\Algorithm\HS256;
use Jose\Component\Encryption\Compression\Deflate;
use Jose\Component\Encryption\Algorithm\KeyEncryption\A256KW;
use Jose\Component\Encryption\Compression\CompressionMethodManager;
use Jose\Component\Encryption\Algorithm\ContentEncryption\A128CBCHS256;

class Bootstrapper {

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
     * Request start
     * @var float
     */
    public static float $duppy_req_start;

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
    public static function doctrineCli(): EntityManager {
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

        /*
           Default global Middlewares
           Middleware ordering: https://www.slimframework.com/docs/v4/images/middleware.png
         */

        $app->add(new EnvironmentMiddleware);

        // Going out, this middleware clears services and dependency per-request cache.
        $app->add(new DuppyServiceMiddleware);

        // Route Rate Limiting (check limiting per route after routing)
        $app->add(new RouteRateLimitMiddleware);

        $app->add(new CORSMiddleware);

        // Slim routing
        $app->addRoutingMiddleware();

        // Global Rate Limiting
        $app->add(new RateLimitMiddleware);
        $app->add(new IpAddress);

        $app->addErrorMiddleware(false, true, true);

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
        $db = new Dependency(fn () => Bootstrapper::configureDatabase());
        $db->inject($container, "database");

        // JSON Web Token (JWS/JWE)
        // todo; please use DI container
        Bootstrapper::configureJWT();

        // Hybridauth external login helper
        $hybridauth = new Dependency(fn () => Bootstrapper::configureHybridAuth());
        $hybridauth->setPerRequestConfig();
        $hybridauth->inject($container, "authHandler");

        // PHPMailer
        $mailer = new Dependency(fn () => Bootstrapper::configureMailer());
        $mailer->setPerRequestConfig();
        $mailer->inject($container, "mailer");

        // OneBlade Templating
        $templateHandler = new Dependency(fn () => Bootstrapper::configureTemplates());
        $templateHandler->inject($container, "templateHandler");

        // Rate Limit Adapter
        $rateLimitAdapter = new Dependency(fn () => Bootstrapper::configureRateLimiterAdapter());
        $rateLimitAdapter->inject($container, "rateLimitAdapter");
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
            'user' => Env::G('DB_USERNAME'),
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

        $req = Bootstrapper::getCurrentRequest();
        $url = strtok($req->getUri(), "?");

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
     *
     * @return PHPMailer
     * @throws DependencyException
     * @throws DuppyException
     * @throws NotFoundException
     * @throws PHPMailerException
     */
    public static function configureMailer(): PHPMailer {
        $isDev = Env::G("DUPPY_DEVELOPMENT");
        $mailer = new PHPMailer($isDev);

        $mailer->SMTPDebug = $isDev ? SMTP::DEBUG_SERVER : SMTP::DEBUG_OFF;

        $emailSettings = (new Settings)->inst()->getSettings([
            "email.engine", "email.from",
            "email.smtp.host", "email.smtp.port",
        ]);

        if ($emailSettings["email.engine"] === "smtp") {
            $mailer->isSMTP();
            $mailer->Host = $emailSettings["email.smtp.host"];
            $mailer->Port = $emailSettings["email.smtp.port"];

            $user = Env::G("SMTP_USERNAME");

            if (!empty($user)) {
                $mailer->SMTPAuth = true;

                $mailer->Username = $user;
                $mailer->Password = Env::G("SMTP_PASSWORD");
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

        $mailer->setFrom($emailSettings["email.from"]);

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
        return new Adapter\Memcached(new Memcached);
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
     * Returns if the API is allowed to redirect requests to the webclient
     * 
     * @return bool
     */
    public static function currentAllowsRedirect(): bool {
        $currentRequest = Bootstrapper::getCurrentRequest();

        if ($currentRequest == null) {
            return false;
        }

        // Allow redirects unless the request specifically asks
        $noRedirect = $currentRequest->hasHeader("X-No-Redirect");
        return !$noRedirect;
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
     * @return EntityManager
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function getDatabase(): EntityManager {
        return self::getContainer()->get("database");
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

    /**
     * @return float
     */
    public static function getRequestStart(): float {
        return Bootstrapper::$duppy_req_start;
    }

    /**
     * @param float $start
     */
    public static function setRequestStart(float $start) {
        Bootstrapper::$duppy_req_start = $start;
    }

}
