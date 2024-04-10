<?php

use App\Environment;
use App\Http\HttpFactory;
use Doctrine\DBAL\Connection;
use GuzzleHttp\Client as HttpClient;
use Intervention\Image\Drivers\Gd\Driver as ImageManagerGdDriver;
use Intervention\Image\ImageManager;
use Laminas\Escaper\Escaper;
use Monolog\Handler\StreamHandler;
use Monolog\Level as LogLevel;
use Monolog\Logger;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Slim\Factory\ServerRequestCreatorFactory;
use Slim\Handlers\Strategies\RequestResponse;
use Slim\Interfaces\RouteParserInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Mailer\Bridge\Amazon\Transport\SesTransportFactory;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\Dsn as MailerDsn;

return [
    // Slim app
    Slim\App::class => function (
        ContainerInterface $di,
        LoggerInterface $logger
    ) {
        $httpFactory = new HttpFactory();

        ServerRequestCreatorFactory::setSlimHttpDecoratorsAutomaticDetection(false);
        ServerRequestCreatorFactory::setServerRequestCreator($httpFactory);

        $app = new Slim\App(
            responseFactory: $httpFactory,
            container: $di,
        );

        $routeCollector = $app->getRouteCollector();
        $routeCollector->setDefaultInvocationStrategy(new RequestResponse());

        if (Environment::isProduction()) {
            $routeCollector->setCacheFile(Environment::getTempDirectory() . '/app_routes.cache.php');
        }

        call_user_func(include(__DIR__ . '/routes.php'), $app);

        // System middleware for routing and body parsing.
        $app->addBodyParsingMiddleware();
        $app->addRoutingMiddleware();

        // Redirects and updates that should happen before system middleware.
        $app->add(new App\Middleware\RemoveSlashes());
        $app->add(new App\Middleware\ApplyXForwardedProto());
        $app->add(new App\Middleware\GetRemoteIp());

        // Add an error handler for most in-controller/task situations.
        $errorHandler = $app->addErrorMiddleware(
            Environment::isDev(),
            true,
            true,
            $logger
        );
        $errorHandler->setDefaultErrorHandler(App\Http\ErrorHandler::class);

        return $app;
    },

    RouteParserInterface::class => fn(Slim\App $app) => $app->getRouteCollector()->getRouteParser(),

    // Console
    ConsoleApplication::class => function (
        ContainerInterface $di
    ) {
        $console = new ConsoleApplication(
            'WaterWolf CLI',
            '1.0.0'
        );

        // Add commands here
        $commandLoader = new ContainerCommandLoader(
            $di,
            [
                'backup' => App\Console\Command\BackupCommand::class,
                'init' => App\Console\Command\InitCommand::class,
                'migrate' => App\Console\Command\MigrateCommand::class,
                'seed' => App\Console\Command\SeedCommand::class,
                'sync' => App\Console\Command\SyncCommand::class,
                'uptime-wait' => App\Console\Command\UptimeWaitCommand::class,
            ]
        );

        $console->setCommandLoader($commandLoader);

        return $console;
    },

    // Escapes HTML, HTML attributes and URL chunks.
    Escaper::class => static fn() => new Escaper('utf-8'),

    // Database Abstraction Layer
    Connection::class => static function () {
        $connectionParams = [
            ...Environment::getDatabaseInfo(),
            'driver' => 'pdo_mysql',
            'options' => [
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8MB4' COLLATE 'utf8mb4_unicode_ci'",
            ],
        ];

        return Doctrine\DBAL\DriverManager::getConnection($connectionParams);
    },

    // Image manager (resizer, processor, etc.)
    ImageManager::class => static fn() => new ImageManager(new ImageManagerGdDriver()),

    // E-mail delivery service
    Mailer::class => static function () {
        $dsnString = $_ENV['MAILER_DSN']
            ?? throw new \RuntimeException('Mailer not configured.');

        $dsn = MailerDsn::fromString($dsnString);
        $transport = (new SesTransportFactory())->create($dsn);

        return new Mailer($transport);
    },

    // HTTP client
    HttpClient::class => static fn() => new HttpClient([
        'headers' => [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36',
        ],
    ]),

    // Filesystem Utilities
    Filesystem::class => static fn() => new Filesystem(),

    // PSR-6 cache
    CacheItemPoolInterface::class => static function (
        Logger $logger
    ) {
        $cacheInterface = new FilesystemAdapter(
            directory: Environment::getTempDirectory() . '/cache'
        );
        $cacheInterface->setLogger($logger);
        return $cacheInterface;
    },

    // PSR-16 cache
    CacheInterface::class => static fn(CacheItemPoolInterface $psr6Cache) => new Psr16Cache($psr6Cache),

    // PSR Logger
    Logger::class => function () {
        $logger = new Logger('site');

        $loggingLevel = Environment::isProduction()
            ? LogLevel::Warning
            : LogLevel::Debug;

        $logger->pushHandler(
            new StreamHandler('php://stderr', $loggingLevel, true)
        );

        $logger->pushHandler(
            new Monolog\Handler\RotatingFileHandler(
                '/logs/site.log',
                5,
                $loggingLevel,
                true
            )
        );

        return $logger;
    },

    LoggerInterface::class => DI\Get(Logger::class),
];
