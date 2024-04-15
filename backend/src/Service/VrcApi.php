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

    public const string USER_ID_REGEX = '/^usr_([a-f0-9]{8})-([a-f0-9]{4})-([a-f0-9]{4})-([a-f0-9]{4})-([a-f0-9]{12})$/';
    public const string WORLD_ID_REGEX = '/^wrld_([a-f0-9]{8})-([a-f0-9]{4})-([a-f0-9]{4})-([a-f0-9]{4})-([a-f0-9]{12})$/';

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
        if (preg_match(self::WORLD_ID_REGEX, $worldId)) {
            return $worldId;
        }

        if (str_starts_with($worldId, 'http')) {
            $uri = new Uri($worldId);

            // URLs in the form of:
            // https://vrchat.com/home/world/wrld_bcfd94c8-3d69-4d9b-b610-282c6d8a5b3d
            if (str_starts_with($uri->getPath(), '/home/world')) {
                $uriParts = explode('/', trim($uri->getPath(), '/'));
                return trim($uriParts[2] ?? '');
            }

            // URLs in the form of:
            // https://vrchat.com/home/launch?worldId=wrld_4cf554b4-430c-4f8f-b53e-1f294eed230b&...
            $queryParams = Query::parse($uri->getQuery());
            if (!empty($queryParams['worldid'])) {
                return trim($queryParams['worldid']);
            }
        }

        // Allow for non-standard world IDs from before VRC standardized on them.
        return $worldId;
    }

    public static function parseUserId(string $userId): string
    {
        $userId = trim($userId);
        if (preg_match(self::USER_ID_REGEX, $userId)) {
            return $userId;
        }

        if (str_starts_with($userId, 'http')) {
            $uri = new Uri($userId);

            // URLs in the form of:
            // https://vrchat.com/home/user/usr_fd418fc1-6824-43ff-b31a-18b6a5d16b15
            if (str_starts_with($uri->getPath(), '/home/user')) {
                $uriParts = explode('/', trim($uri->getPath(), '/'));
                return trim($uriParts[2] ?? '');
            }
        }

        // Allow non-standard UIDs from before VRC standardized on UIDs.
        return $userId;
    }
}
