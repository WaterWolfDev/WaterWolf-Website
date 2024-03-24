<?php

namespace App\Controller;

use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;

final readonly class TeamAction
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
        $teamMembers = $this->db->fetchAllAssociative(
            <<<'SQL'
                SELECT id, username, title, pronouns, user_img
                FROM web_users
                WHERE is_team = 1
                AND banned != 1
                ORDER BY id ASC
            SQL
        );

        return $request->getView()->renderToResponse(
            $response,
            'team',
            [
                'team_members' => $teamMembers,
            ]
        );
    }
}
