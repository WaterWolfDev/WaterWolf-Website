<?php

namespace App\Controller\Api;

use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;

final readonly class CommentsController
{
    public function __construct(
        private Connection $db
    ) {
    }

    public function listAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $location = $params['location'] ?? null;
        if (empty($location)) {
            throw new \InvalidArgumentException('No comment location provided.');
        }

        $comments = $this->db->fetchAllAssociative(
            <<<'SQL'
                SELECT c.*, u.username
                FROM web_comments AS c
                JOIN waterwolf.web_users u on c.creator = u.id
                WHERE c.location=:location
                AND u.banned != 1
                ORDER BY c.id DESC
            SQL,
            [
                'location' => $location,
            ]
        );

        return $response->withJson($comments);
    }

    public function postAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $location = $params['location'] ?? null;
        if (empty($location)) {
            throw new \InvalidArgumentException('No comment location provided.');
        }

        $currentUser = $request->getCurrentUser();
        assert($currentUser !== null);

        $postData = $request->getParsedBody();

        $this->db->insert(
            'web_comments',
            [
                'comment' => $postData['comment'],
                'location' => $location,
                'creator' => $currentUser['id'],
                'tstamp' => time(),
            ]
        );

        return $response->withJson([
            'success' => true,
        ]);
    }

    public function deleteAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $location = $params['location'] ?? null;
        if (empty($location)) {
            throw new \InvalidArgumentException('No comment location provided.');
        }

        $id = $request->getParam('id');
        if (empty($id)) {
            throw new \InvalidArgumentException('No ID provided.');
        }

        $this->db->delete(
            'web_comments',
            [
                'id' => $id,
                'location' => $location,
            ]
        );

        return $response->withJson([
            'success' => true,
        ]);
    }
}
