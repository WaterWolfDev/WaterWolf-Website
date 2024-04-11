<?php

namespace App\Service\VrcApi;

use Psr\SimpleCache\CacheInterface;
use Spatie\GuzzleRateLimiterMiddleware\Store;

final readonly class RateLimitStore implements Store
{
    public const string CACHE_KEY = 'vrcapi_rate_limit';

    public function __construct(
        private CacheInterface $psrCache
    ) {
    }

    public function get(): array
    {
        return $this->psrCache->get(self::CACHE_KEY, []);
    }

    public function push(int $timestamp, int $limit): void
    {
        $entries = $this->get();
        $entries[] = $timestamp;

        $this->psrCache->set(self::CACHE_KEY, $entries, $limit);
    }
}
