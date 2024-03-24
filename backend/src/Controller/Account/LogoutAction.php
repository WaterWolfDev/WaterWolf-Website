<?php

namespace App\Controller\Account;

use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;

final readonly class LogoutAction
{
    public function __construct(
        private Connection $db
    ) {
    }

    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $user = $request->getCurrentUser();

        if ($user !== null) {
            $this->db->update(
                'web_users',
                [
                    'online' => '0',
                ],
                [
                    'id' => $user['id'],
                ]
            );
        }

        $session = $request->getSession();

        $session->clear();
        $session->regenerate();

        return $response->withRedirect('/');
    }
}
