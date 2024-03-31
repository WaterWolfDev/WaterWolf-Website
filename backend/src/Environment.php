<?php

namespace App;

final class Environment
{
    // Consts represent the environment variable names as passed to the app.
    private const APPLICATION_ENV = 'APPLICATION_ENV';

    private const DB_HOST = 'MARIADB_SERVER';
    private const DB_USER = 'MARIADB_USER';
    private const DB_PASS = 'MARIADB_PASSWORD';
    private const DB_NAME = 'MARIADB_DATABASE';

    private const MAILER_DSN = 'MAILER_DSN';

    private const MEDIA_SITE_URL = 'MEDIA_SITE_URL';
    private const PHP_MEDIA_PATH = 'PHP_MEDIA_PATH';

    private const VRCHAT_API_KEY = 'VRCHAT_API_KEY';

    private const DISCORD_WEBHOOK_URL = 'DISCORD_WEBHOOK_URL';

    public function __construct(
        private readonly array $data
    ) {
    }

    public function getApplicationEnv(): string
    {
        return $this->data[self::APPLICATION_ENV] ?? 'production';
    }

    public function isProduction(): bool
    {
        return $this->getApplicationEnv() === 'production';
    }

    public function isDev(): bool
    {
        return $this->getApplicationEnv() !== 'production';
    }

    public function isCli(): bool
    {
        return ('cli' === PHP_SAPI);
    }

    public function getBaseDirectory(): string
    {
        return dirname(__DIR__, 2);
    }

    public function getParentDirectory(): string
    {
        return dirname($this->getBaseDirectory());
    }

    public function getTempDirectory(): string
    {
        return $this->getParentDirectory() . '/www_tmp';
    }

    public function getDatabaseInfo(): array
    {
        return [
            'host' => $this->data[self::DB_HOST],
            'user' => $this->data[self::DB_USER],
            'password' => $this->data[self::DB_PASS],
            'dbname' => $this->data[self::DB_NAME],
        ];
    }

    public function getMailerDsn(): string
    {
        return $this->data[self::MAILER_DSN]
            ?? throw new \RuntimeException('Mailer not configured.');
    }

    public function getMediaUrl(): string
    {
        return $this->isDev()
            ? '/media/site'
            : $this->data[self::MEDIA_SITE_URL];
    }

    public function getMediaPath(): string
    {
        return $this->isDev()
            ? $this->getBaseDirectory() . '/web/media/site'
            : $this->data[self::PHP_MEDIA_PATH];
    }

    public function getVrcApiKey(): string
    {
        return $this->data[self::VRCHAT_API_KEY]
            ?? throw new \RuntimeException('VRChat API key not configured.');
    }

    public function getDiscordWebookUrl(): string
    {
        return $this->data[self::DISCORD_WEBHOOK_URL]
            ?? throw new \RuntimeException('Discord webhook URL not configured.');
    }
}
