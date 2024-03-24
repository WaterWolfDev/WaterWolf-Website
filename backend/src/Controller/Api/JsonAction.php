<?php

namespace App\Controller\Api;

use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Psr\Http\Message\ResponseInterface;

final readonly class JsonAction
{
    public function __construct(
        private Connection $db
    ) {
    }

    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        try {
            $userCount = $this->db->fetchOne(
                <<<'SQL'
                    SELECT count(*) AS user_count
                    FROM web_users
                SQL
            );

            return $response->withJson([
                'success' => true,
                'total_users' => $userCount,
            ]);
        } catch (Exception $e) {
            return $response->withJson([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
