<?php

namespace App;

use League\Flysystem\Config;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UrlGeneration\PublicUrlGenerator;

final class Media
{
    private ?Filesystem $fs = null;

    public function __construct(
        private readonly Environment $environment
    ) {
    }

    public function getFilesystem(): Filesystem
    {
        if (null === $this->fs) {
            $adapter = new LocalFilesystemAdapter(
                $this->environment->getMediaPath()
            );

            $this->fs = new Filesystem(
                $adapter,
                config: [
                    'base_url' => $this->environment->getMediaUrl(),
                ],
                publicUrlGenerator: new class implements PublicUrlGenerator {
                    public function publicUrl(string $path, Config $config): string
                    {
                        $baseUrl = $config->get('base_url');
                        $url = implode("/", array_map("rawurlencode", explode("/", $path)));
                        return $baseUrl . '/' . ltrim($url, '/');
                    }
                }
            );
        }

        return $this->fs;
    }

    public function mediaUrl(string $url): string
    {
        return $this->getFilesystem()->publicUrl($url);
    }

    public function avatarUrl(string|bool|null $userImg): string
    {
        return (!empty($userImg))
            ? $this->mediaUrl('/img/profile/' . $userImg)
            : '/static/img/avatar.webp';
    }

    public function djAvatarUrl(
        string|bool|null $djImg,
        string|bool|null $userImg
    ): string {
        return (!empty($djImg))
            ? $this->mediaUrl('/img/djs/' . $djImg)
            : $this->avatarUrl($userImg);
    }
}
