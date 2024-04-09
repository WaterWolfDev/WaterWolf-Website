<?php

/*
 * Event Configuration: Defective Equipment
 */

// Times in the configured zone below (currently EST/EDT)
$tz = 'America/New_York';

/**
 * @var array<array{
 *      name: string,
 *      time: string,
 *      timestamp: int,
 *      datetime: DateTimeImmutable,
 * }> $djs
 */
$djs = [
    [
        'name' => 'Beamflare',
        'time' => '2024-03-09 15:00',
    ],
    [
        'name' => 'Birds of Prey',
        'time' => '2024-03-09 16:00',
    ],
    [
        'name' => 'Blaze/Poptart',
        'time' => '2024-03-09 17:00',
    ],
    [
        'name' => 'Beeb',
        'time' => '2024-03-09 18:00',
    ],
    [
        'name' => 'Draven',
        'time' => '2024-03-09 19:00',
    ],
    [
        'name' => 'P I N K',
        'time' => '2024-03-09 20:00',
    ],
    [
        'name' => 'DJ Altro',
        'time' => '2024-03-09 21:00',
    ],
    [
        'name' => 'GOM8 (feat. Special Guest)',
        'time' => '2024-03-09 22:00',
    ],
    [
        'name' => 'Physwolf',
        'time' => '2024-03-09 23:00',
    ],
    [
        'name' => 'Morganite',
        'time' => '2024-03-10 00:00',
    ],
    [
        'name' => 'Warned1',
        'time' => '2024-03-10 01:00',
    ],
    [
        'name' => 'Sebris',
        'time' => '2024-03-10 03:00',
    ],
];

foreach ($djs as &$dj) {
    $dj['datetime'] = new \DateTimeImmutable($dj['time'], new DateTimeZone($tz));
    $dj['timestamp'] = $dj['datetime']->getTimestamp();
    $dj['time'] = $dj['datetime']->format('g:ia');
}
unset($dj);

$startDate = $djs[0]['timestamp'];

return [
    'tz' => $tz,
    'djs' => $djs,
    'startDate' => $startDate,
];
