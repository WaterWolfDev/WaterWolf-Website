<?php

namespace App\Controller\Api;

use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;

final readonly class PosterSpecAction
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
        $groups = $this->db->fetchAllKeyValue(
            <<<'SQL'
                SELECT id, name
                FROM web_groups
            SQL
        );

        $types = $this->db->fetchAllKeyValue(
            <<<'SQL'
                SELECT id, description
                FROM web_poster_types
            SQL
        );

        return $response->withJson([
            [
                'key' => 'group',
                'name' => 'Group',
                'type' => 'options',
                'values' => $groups,
            ],
            [
                'key' => 'type',
                'name' => 'Type',
                'type' => 'options',
                'values' => $types,
            ],
            [
                'key' => 'collection',
                'name' => 'Collection',
                'type' => 'text',
            ],
        ], null, JSON_PRETTY_PRINT);
    }
}
