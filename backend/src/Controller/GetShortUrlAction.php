<?php

namespace App\Controller;

use App\Exception\NotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;

/**
 * Handler for short URLs (i.e. via `wtr.wf`)
 */
final readonly class GetShortUrlAction
{
    public function __construct(
        private Connection $db
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $url = trim($params['url'] ?? $request->getParam('url', ''), '/');

        if (empty($url)) {
            return $response->withRedirect('/');
        }

        $shortUrl = $this->db->fetchAssociative(
            <<<'SQL'
                SELECT id, long_url
                FROM web_short_urls
                WHERE short_url = :url
            SQL,
            [
                'url' => $url,
            ]
        );

        if (false === $shortUrl) {
            throw new NotFoundException($request, 'Page not found!');
        }

        $this->db->executeQuery(
            <<<'SQL'
                UPDATE web_short_urls
                SET views=views+1
                WHERE id = :id
            SQL,
            [
                'id' => $shortUrl['id'],
            ]
        );

        return $response->withRedirect($shortUrl['long_url']);
    }
}
