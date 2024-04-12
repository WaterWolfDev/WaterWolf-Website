<?php

namespace App\Service;

use App\Service\VrcApi\AuthMiddleware;
use App\Service\VrcApi\RateLimitStore;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Query;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\ResponseInterface;
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

    public function getDisplayNameFromUid(string $uid): string
    {
        $userInfo = self::processResponse(
            $this->httpClient->get(
                sprintf('users/%s', $uid)
            )
        );

        if (empty($userInfo) || empty($userInfo['displayName'])) {
            throw new \RuntimeException('Could not fetch user information!');
        }

        return $userInfo['displayName'];
    }

    public static function processResponse(ResponseInterface $response): mixed
    {
        $body = $response->getBody()->getContents();

        if (json_validate($body)) {
            return json_decode(
                $body,
                true,
                JSON_THROW_ON_ERROR
            );
        }

        return $body;
    }

    public static function parseWorldId(string $worldId): string
    {
        $worldId = trim($worldId);

        if (str_starts_with($worldId, 'wrld')) {
            return $worldId;
        }

        if (str_starts_with($worldId, 'http')) {
            $uri = new Uri($worldId);

            // URLs in the form of:
            // https://vrchat.com/home/world/wrld_bcfd94c8-3d69-4d9b-b610-282c6d8a5b3d
            if (str_starts_with($uri->getPath(), '/home/world')) {
                $uriParts = explode('/', trim($uri->getPath(), '/'));

                if (str_starts_with($uriParts[2], 'wrld')) {
                    return $uriParts[2];
                }
            }

            // URLs in the form of:
            // https://vrchat.com/home/launch?worldId=wrld_4cf554b4-430c-4f8f-b53e-1f294eed230b&...
            $queryParams = Query::parse($uri->getQuery());

            if (isset($queryParams['worldId']) && str_starts_with($queryParams['worldId'], 'wrld')) {
                return $queryParams['worldId'];
            }
        }

        throw new \InvalidArgumentException('Could not determine world ID from URL.');
    }

    public static function parseUserId(string $userId): string
    {
        $userId = trim($userId);
        ;

        if (str_starts_with($userId, 'usr')) {
            return $userId;
        }

        if (str_starts_with($userId, 'http')) {
            $uri = new Uri($userId);

            // URLs in the form of:
            // https://vrchat.com/home/user/usr_fd418fc1-6824-43ff-b31a-18b6a5d16b15
            if (str_starts_with($uri->getPath(), '/home/user')) {
                $uriParts = explode('/', trim($uri->getPath(), '/'));

                if (str_starts_with($uriParts[2], 'usr')) {
                    return $uriParts[2];
                }
            }
        }

        throw new \InvalidArgumentException('Could not determine user ID from URL.');
    }
}
