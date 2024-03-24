<?php

namespace App\Controller;

use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;

final readonly class TalentAction
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
        $skills = $this->db->fetchAllAssociative(
            <<<'SQL'
                SELECT s.skill, COUNT(s.id) as occurrences
                FROM web_user_skills s
                JOIN web_users u ON s.creator = u.id
                WHERE u.banned != 1
                GROUP BY skill
            SQL
        );

        return $request->getView()->renderToResponse(
            $response,
            'talent',
            [
                'skills' => $skills,
            ]
        );
    }
}
