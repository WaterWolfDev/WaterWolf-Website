<?php

namespace App;

use Aws\S3\S3Client;
use GuzzleHttp\Psr7\Uri;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Config;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UrlGeneration\PublicUrlGenerator;

final class Media
{
    public static function getFilesystem(): Filesystem
    {
        static $fs;

        if (!$fs) {
            $adapter = (Environment::isProduction() && !empty($_ENV['MEDIA_REPOSITORY']))
                ? self::getRemoteAdapter()
                : self::getLocalAdapter();

            return new Filesystem(
                $adapter,
                publicUrlGenerator: new class implements PublicUrlGenerator {
                    public function publicUrl(string $path, Config $config): string
                    {
                        return mediaUrl($path);
                    }
                }
            );
        }

        return $fs;
    }

    private static function getLocalAdapter(): FilesystemAdapter
    {
        return new LocalFilesystemAdapter(
            $_ENV['PHP_MEDIA_PATH']
        );
    }

    private static function getRemoteAdapter(): FilesystemAdapter
    {
        $key = $_ENV['AWS_ACCESS_KEY_ID'] ?? null;
        $secret = $_ENV['AWS_SECRET_ACCESS_KEY'] ?? null;
        $dsn = $_ENV['MEDIA_REPOSITORY'] ?? null;

        if (empty($key) || empty($secret) || empty($dsn)) {
            throw new \InvalidArgumentException('S3 credentials not configured.');
        }

        $dsnParsed = new Uri(str_replace('s3:', '', $dsn));

        $s3Client = new S3Client([
            'credentials' => [
                'key' => $key,
                'secret' => $secret,
            ],
            'region' => 'auto',
            'endpoint' => $dsnParsed->getScheme() . '://' . $dsnParsed->getHost(),
        ]);

        return new AwsS3V3Adapter($s3Client, ltrim($dsnParsed->getPath(), '/'));
    }
}
