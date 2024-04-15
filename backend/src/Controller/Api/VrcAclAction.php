<?php

namespace App\Controller\Api;

use App\Exception\NotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

final readonly class VrcAclAction
{
    public function __construct(
        private Connection $db,
        private CacheInterface $psrCache
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $allowedTypes = [
            'team',
        ];

        $type = $params['type'] ?? 'team';
        if (!in_array($type, $allowedTypes, true)) {
            throw new NotFoundException($request, 'ACL type not found.');
        }

        $cacheKey = 'vrc_acl_' . $type;
        if ($this->psrCache->has($cacheKey)) {
            $users = $this->psrCache->get($cacheKey);
        } else {
            $qb = $this->db->createQueryBuilder()
                ->select('u.vrchat')
                ->from('web_users', 'u')
                ->where('(u.vrchat IS NOT NULL AND u.vrchat != "")');

            $qb = match ($type) {
                'team' => $qb->andWhere('u.is_team = 1'),
            };

            $users = $qb->fetchAllAssociative();

            $this->psrCache->set($cacheKey, $users, 60);
        }

        return $response->withJson([
            "_instructions" => [
                "This ACL can be used as a remote whitelist for TXL in VRChat.",
                "To use it:",
                " - Add this URL as the 'Remote String URL' value.",
                " - Select 'JSON Array' as the 'Remote String Format'.",
                " - Enter 'users' (without quotes) as the 'JSON Object Path'.",
                " - Enter 'vrchat' (without quotes) as the 'JSON Entry Path'.",
                "If using 'Periodic Refresh', it is recommended to use a longer 'Refresh Period' (at least 600).",
            ],
            'users' => $users,
        ], null, JSON_PRETTY_PRINT);
    }
}
