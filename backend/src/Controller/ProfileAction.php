<?php

namespace App\Controller;

use App\Exception\NotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;

final readonly class ProfileAction
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
        $username = $params['user'] ?? $request->getParam('user', $request->getParam('id'));

        $profile = $this->db->fetchAssociative(
            <<<'SQL'
                SELECT *
                FROM web_users
                WHERE username = :username
                AND banned != 1
                LIMIT 1
            SQL,
            [
                'username' => $username,
            ]
        );

        if ($profile === false) {
            throw NotFoundException::user($request);
        }

        return $request->getView()->renderToResponse(
            $response,
            'profile',
            [
                'profile' => $profile,
            ]
        );
    }
}
