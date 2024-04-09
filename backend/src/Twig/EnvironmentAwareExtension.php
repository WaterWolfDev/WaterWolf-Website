<?php

namespace App\Twig;

use App\Environment;
use App\Media;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

final class EnvironmentAwareExtension extends AbstractExtension implements GlobalsInterface
{
    public function getGlobals(): array
    {
        return [
            'base_dir' => Environment::getBaseDirectory(),
            'temp_dir' => Environment::getTempDirectory(),
            'is_production' => Environment::isProduction(),
            'is_dev' => Environment::isDev(),
            'is_cli' => Environment::isCli(),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('mediaUrl', [$this, 'mediaUrl'], [
                'is_safe' => ['html', 'js'],
            ]),
            new TwigFunction('avatarUrl', [$this, 'avatarUrl'], [
                'is_safe' => ['html', 'js'],
            ]),
            new TwigFunction('djAvatarUrl', [$this, 'djAvatarUrl'], [
                'is_safe' => ['html', 'js'],
            ]),
            new TwigFunction('posterThumbUrl', [$this, 'posterThumbUrl'], [
                'is_safe' => ['html', 'js'],
            ]),
            new TwigFunction('humanTime', [$this, 'humanTime']),
            new TwigFunction('timeAgo', [$this, 'timeAgo']),
        ];
    }

    public function mediaUrl(string $url): string
    {
        static $mediaBaseUrl;
        if (!$mediaBaseUrl) {
            $mediaBaseUrl = $_ENV['MEDIA_SITE_URL'] ?? null;
        }

        if (empty($mediaBaseUrl)) {
            throw new \RuntimeException('Media base URL not configured.');
        }

        // Encode individual portions of the URL between slashes.
        $url = implode("/", array_map("rawurlencode", explode("/", $url)));

        return $mediaBaseUrl . '/' . ltrim($url, '/');
    }

    public function avatarUrl(string|bool|null $userImg): string
    {
        return (!empty($userImg))
            ? $this->mediaUrl(Media::avatarPath($userImg))
            : '/static/img/avatar.webp';
    }

    public function djAvatarUrl(
        string|bool|null $djImg,
        string|bool|null $userImg
    ): string {
        return (!empty($djImg))
            ? $this->mediaUrl(Media::djAvatarPath($djImg))
            : $this->avatarUrl($userImg);
    }

    public function posterThumbUrl(
        string|null $posterImg
    ): string {
        if (!empty($posterImg)) {
            return $this->mediaUrl(Media::posterPath($posterImg));
        }

        return '/static/img/no_poster_thumb.jpg';
    }

    public function humanTime(string|int|null $timestamp = "", string $format = 'D, M d, Y \a\t g:i A'): string
    {
        if (empty($timestamp) || !is_numeric($timestamp)) {
            $timestamp = time();
        }

        return date($format, $timestamp);
    }

    public function timeAgo(int|null $time): string
    {
        if ($time === null) {
            return '';
        }

        $periods = ["sec", "min", "hour", "day", "week", "month", "year", "decade"];
        $lengths = ["60", "60", "24", "7", "4.35", "12", "10"];

        $difference = time() - $time;
        for ($j = 0; $difference >= $lengths[$j] && $j < count($lengths) - 1; $j++) {
            $difference /= $lengths[$j];
        }

        $difference = round($difference);
        if ($difference != 1) {
            $periods[$j] .= "s";
        }

        return "$difference $periods[$j] ago";
    }
}
