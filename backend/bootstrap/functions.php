<?php

use App\Environment;
use Symfony\Component\Filesystem\Filesystem;

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
 * Input Escaping
 */

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
