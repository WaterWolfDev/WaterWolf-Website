<?php

namespace App\Service;

use App\Environment;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

final readonly class Discord
{
    public function __construct(
        private LoggerInterface $logger,
        private Client $http
    ) {
    }

    public function sendMessage(
        string $webhookUrl,
        string $message,
        float|int $color,
        ?string $title_set,
        ?string $desc,
        ?string $img_url
    ): string {
        $this->logger->debug(
            'Sending Discord message...',
            func_get_args()
        );

        if (!Environment::isProduction()) {
            return '';
        }

        $data = [
            'content' => $message,
            'embeds' => [
                [
                    'title' => $title_set,
                    'description' => $desc,
                    'color' => $color,
                    'thumbnail' => [
                        'url' => $img_url,
                    ],
                ],
            ],
        ];

        $response = $this->http->post(
            $webhookUrl,
            [
                'json' => $data,
            ]
        );

        return $response->getBody()->getContents();
    }
}
