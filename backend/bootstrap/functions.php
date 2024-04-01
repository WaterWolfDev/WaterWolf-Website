<?php

/*
 * Input Escaping
 */

function escapeHtml(?string $html): string
{
    return htmlspecialchars($html ?? '', encoding: 'UTF-8');
}

function escapeHtmlAttribute(?string $htmlAttribute): string
{
    static $escaper;
    if (!$escaper) {
        $escaper = new Laminas\Escaper\Escaper('UTF-8');
    }

    return $escaper->escapeHtmlAttr($htmlAttribute ?? '');
}

function escapeJs(mixed $string): string
{
    return json_encode($string, JSON_THROW_ON_ERROR);
}

/*
 * User-uploaded media
 */

function mediaUrl(string $url): string
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

function avatarUrl(string|bool|null $userImg): string
{
    return (!empty($userImg))
        ? mediaUrl('/img/profile/' . $userImg)
        : '/static/img/avatar.webp';
}

function djAvatarUrl(
    string|bool|null $djImg,
    string|bool|null $userImg
): string {
    return (!empty($djImg))
        ? mediaUrl('/img/djs/' . $djImg)
        : avatarUrl($userImg);
}

function humanTime(string|int|null $timestamp = "", string $format = 'D, M d, Y \a\t g:i A'): string
{
    if (empty($timestamp) || !is_numeric($timestamp)) {
        $timestamp = time();
    }

    return date($format, $timestamp);
}

function timeAgo(int|null $time): string
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
