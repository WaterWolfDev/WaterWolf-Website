<?php

namespace App\Service;

use App\Environment;
use GuzzleHttp\Client;

final readonly class VrcApi
{
    public function __construct(
        private Environment $environment,
        private Client $http
    ) {
    }

    /**
     * Send a request to the API proxy server
     *
     * @param string $method The HTTP method to perform (POST, GET, PUT, etc)
     * @param string $path The path to use (/api/1/visits etc)
     * @param string|null $body The HTTP body (if any, set to null if not wanted)
     * @param bool $priority If the request should use the high-priority queue
     * @param bool $async If the request should be async (non-failable, ideal for friend requests/invite requests)
     * @return string|array
     */
    public function sendRequest(
        string $method,
        string $path,
        ?string $body = null,
        bool $priority = false,
        bool $async = false
    ): string|array {
        /** @noinspection HttpUrlsUsage */
        $uri = 'http://149.106.100.136:8124/' . $path;

        $requestConfig = [
            'headers' => [
                'Authorization' => $this->environment->getVrcApiKey(),
            ],
        ];

        if ($priority) {
            $requestConfig['headers']['X-Priority'] = 'high';
        }
        if ($async) {
            $requestConfig['headers']['X-Background'] = '1';
        }

        if ($body !== null) {
            $requestConfig['json'] = $body;
        }

        $response = $this->http->request(
            $method,
            $uri,
            $requestConfig
        );

        if ($response->getHeaderLine('Content-Type') === 'application/json') {
            return json_decode(
                $response->getBody()->getContents(),
                true,
                JSON_THROW_ON_ERROR
            );
        } else {
            return $response->getBody()->getContents();
        }
    }
}
