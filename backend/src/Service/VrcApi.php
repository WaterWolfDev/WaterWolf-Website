<?php

namespace App\Service;

use App\Service\VrcApi\AuthMiddleware;
use App\Service\VrcApi\RateLimitStore;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Spatie\GuzzleRateLimiterMiddleware\RateLimiterMiddleware;

final readonly class VrcApi
{
    public const string VRCAPI_BASE_URL = 'https://api.vrchat.cloud/api/1/';

    private Client $httpClient;

    public function __construct(
        LoggerInterface $logger,
        AuthMiddleware $authMiddleware,
        RateLimitStore $rateLimitStore
    ) {
        $stack = HandlerStack::create();
        $stack->push(
            Middleware::log(
                $logger,
                new MessageFormatter('VRCAPI client {method} call to {uri} produced response {code}'),
                LogLevel::DEBUG
            )
        );
        $stack->push(RateLimiterMiddleware::perSecond(1, $rateLimitStore));
        $stack->push($authMiddleware);

        $this->httpClient = new Client([
            'handler' => $stack,
            'base_uri' => self::VRCAPI_BASE_URL,
            'headers' => [
                'User-Agent' => 'WaterWolf/1.0 Isaac@waterwolf.club',
            ],
        ]);
    }

    public function getHttpClient(): Client
    {
        return $this->httpClient;
    }
}
