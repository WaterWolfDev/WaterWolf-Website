<?php

namespace App\Controller\Traits;

use App\Exception\PermissionDeniedException;
use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;

/**
 * @property Connection $db
 */
trait HasComments
{
    protected function handlePost(
        ServerRequest $request,
        Response $response,
        string $location
    ): ResponseInterface {
        $currentUser = $request->getCurrentUser();
        assert($currentUser !== null);

        switch ($request->getParam('do', 'post')) {
            case 'delete':
                if (!$currentUser->isMod()) {
                    throw new PermissionDeniedException($request);
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

                $request->getFlash()->success('Comment deleted.');
                break;

            case 'post':
            default:
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

                $request->getFlash()->success('Comment posted.');
                break;
        }

        return $response->withRedirect(
            (string)$request->getUri()
        );
    }

    protected function getComments(string $location): array
    {
        return $this->db->fetchAllAssociative(
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
    }
}
