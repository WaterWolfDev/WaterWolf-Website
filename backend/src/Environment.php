<?php

namespace App;

final class Environment
{
    public static function getApplicationEnv(): string
    {
        return $_ENV['APPLICATION_ENV'] ?? 'production';
    }

    public static function isProduction(): bool
    {
        return self::getApplicationEnv() === 'production';
    }

    public static function isDev(): bool
    {
        return self::getApplicationEnv() !== 'production';
    }

    public static function isCli(): bool
    {
        return ('cli' === PHP_SAPI);
    }

    public static function getBaseDirectory(): string
    {
        return dirname(__DIR__, 2);
    }

    public static function getParentDirectory(): string
    {
        return dirname(self::getBaseDirectory());
    }

    public static function getTempDirectory(): string
    {
        return self::getParentDirectory() . '/www_tmp';
    }

    public static function getDatabaseInfo(): array
    {
        return [
            'host' => $_ENV['MARIADB_SERVER'],
            'user' => $_ENV['MARIADB_USER'],
            'password' => $_ENV['MARIADB_PASSWORD'],
            'dbname' => $_ENV['MARIADB_DATABASE'],
        ];
    }
}
