<?php

namespace App\Controller\Posters;

use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;

final readonly class GetFaqAction
{
    public function __construct(
        private Connection $db,
    ) {
    }

    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $baseUrl = $request->getRouter()->fullUrlFor(
            $request->getUri(),
            'posters'
        );

        $groups = $this->db->fetchAllAssociative(
            <<<'SQL'
                SELECT *
                FROM web_groups
            SQL
        );

        $types = $this->db->fetchAllAssociative(
            <<<'SQL'
                SELECT *
                FROM web_poster_types
            SQL
        );

        $exampleGroup = $groups[array_key_last($groups)];
        $exampleType = $types[array_key_last($types)];

        return $request->getView()->renderToResponse(
            $response,
            'posters/faq',
            [
                'baseUrl' => $baseUrl,
                'groups' => $groups,
                'exampleGroup' => $exampleGroup,
                'types' => $types,
                'exampleType' => $exampleType,
            ]
        );
    }
}
