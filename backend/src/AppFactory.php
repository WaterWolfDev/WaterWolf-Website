<?php

declare(strict_types=1);

namespace App;

use DI\ContainerBuilder;
use Monolog\ErrorHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Slim\App as SlimApp;
use Symfony\Component\Console\Application as ConsoleApplication;

final class AppFactory
{
    public static function createApp(
        array $appEnvironment = []
    ): SlimApp {
        $environment = self::buildEnvironment($appEnvironment);
        $di = self::buildContainer($environment);
        return $di->get(SlimApp::class);
    }

    public static function createCli(
        array $appEnvironment = []
    ): ConsoleApplication {
        $environment = self::buildEnvironment($appEnvironment);
        $di = self::buildContainer($environment);

        // Some CLI commands require the App to be injected for routing.
        $di->get(SlimApp::class);

        return $di->get(ConsoleApplication::class);
    }

    public static function buildContainer(Environment $environment): ContainerInterface
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->useAutowiring(true);
        $containerBuilder->useAttributes(true);

        if ($environment->isProduction()) {
            $containerBuilder->enableCompilation($environment->getTempDirectory());
        }

        $containerBuilder->addDefinitions([
            Environment::class => $environment,
        ]);
        $containerBuilder->addDefinitions(dirname(__DIR__) . '/bootstrap/services.php');

        $di = $containerBuilder->build();

        // Monolog setup
        $logger = $di->get(Logger::class);

        $errorHandler = new ErrorHandler($logger);
        $errorHandler->registerFatalHandler();

        return $di;
    }

    /**
     * @param array<string, mixed> $rawEnvironment
     */
    public static function buildEnvironment(array $rawEnvironment = []): Environment
    {
        $_ENV = getenv();
        $rawEnvironment = array_merge(array_filter($_ENV), $rawEnvironment);
        $environment = new Environment($rawEnvironment);

        self::applyPhpSettings($environment);

        return $environment;
    }

    private static function applyPhpSettings(Environment $environment): void
    {
        error_reporting(
            $environment->isProduction()
                ? E_ALL & ~E_NOTICE & ~E_WARNING & ~E_STRICT & ~E_DEPRECATED
                : E_ALL & ~E_NOTICE
        );

        $displayStartupErrors = (!$environment->isProduction() || $environment->isCli())
            ? '1'
            : '0';
        ini_set('display_startup_errors', $displayStartupErrors);
        ini_set('display_errors', $displayStartupErrors);

        ini_set('log_errors', '1');
        ini_set('error_log', '/dev/stderr');

        mb_internal_encoding('UTF-8');
        ini_set('default_charset', 'utf-8');

        if (!headers_sent()) {
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_lifetime', '86400');
            ini_set('session.use_strict_mode', '1');

            session_cache_limiter('');
        }

        date_default_timezone_set('UTC');
    }
}
