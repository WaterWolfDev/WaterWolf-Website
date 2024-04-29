<?php

namespace App\Controller;

use App\Controller\Traits\HasComments;
use App\Exception\NotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;

final readonly class WorldsController
{
    use HasComments;

    public function __construct(
        private Connection $db
    ) {
    }

    public function listAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $worlds = $this->db->fetchAllAssociative(
            <<<'SQL'
                SELECT *
                FROM web_worlds
                ORDER BY id DESC
            SQL
        );

        return $request->getView()->renderToResponse(
            $response,
            'worlds',
            [
                'worlds' => $worlds,
            ]
        );
    }

    public function getAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $id = $params['id'] ?? $request->getParam('v', $request->getParam('id'));
        $commentId = sprintf('world_comment_%s', $id);

        if ($request->isPost()) {
            return $this->handlePost($request, $response, $commentId);
        }

        $world = $this->db->fetchAssociative(
            <<<'SQL'
                SELECT *
                FROM web_worlds
                WHERE id = :id
                LIMIT 1
            SQL,
            [
                'id' => $id,
            ]
        );

        if ($world === false) {
            throw NotFoundException::world($request);
        }

        // Log visit to this page.
        $this->db->executeQuery(
            <<<'SQL'
                UPDATE web_worlds 
                SET hits=hits+1
                WHERE id = :id
            SQL,
            [
                'id' => $world['id'],
            ]
        );

        $sidebar_worlds = $this->db->fetchAllAssociative(
            <<<'SQL'
                SELECT *
                FROM web_worlds
                ORDER BY RAND()
                LIMIT 15
            SQL
        );

        return $request->getView()->renderToResponse(
            $response,
            'world',
            [
                'world' => $world,
                'sidebar_worlds' => $sidebar_worlds,
                'comments' => $this->getComments($commentId),
            ]
        );
    }
}
