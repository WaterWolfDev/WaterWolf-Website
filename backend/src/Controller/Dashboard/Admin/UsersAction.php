<?php

namespace App\Controller\Dashboard\Admin;

use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;

final readonly class UsersAction
{
    public function __construct(
        private Connection $db,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $users = $this->db->fetchAllAssociative(
            <<<'SQL'
                SELECT *
                FROM web_users
                ORDER BY id ASC
            SQL
        );

        return $request->getView()->renderToResponse(
            $response,
            'dashboard/admin/users',
            [
                'users' => $users,
            ]
        );
    }
}
