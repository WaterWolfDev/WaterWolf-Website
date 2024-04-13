<?php

namespace App\Controller\Api;

use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;

final readonly class VrcAclAction
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
        $qb = $this->db->createQueryBuilder()
            ->select('u.vrchat')
            ->from('web_users', 'u')
            ->where('(u.vrchat IS NOT NULL AND u.vrchat != "")');

        $qb = match ($params['type'] ?? 'staff') {
            default => $qb->andWhere('u.is_team = 1'),
        };

        $users = $qb->fetchFirstColumn();

        return $response->withJson($users);
    }
}
