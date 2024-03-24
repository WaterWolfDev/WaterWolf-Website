<?php

namespace App\Controller\Dashboard;

use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;

final readonly class ShortUrlsController
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
        $shortUrls = $this->db->fetchAllAssociative(
            <<<'SQL'
                SELECT * 
                FROM web_short_urls
                ORDER BY short_url ASC
            SQL
        );

        return $request->getView()->renderToResponse(
            $response,
            'dashboard/short_urls/list',
            [
                'shortUrls' => $shortUrls,
            ]
        );
    }

    public function createAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $currentUser = $request->getCurrentUser();
        assert($currentUser !== null);

        $row = [];
        $error = null;

        if ($request->isPost()) {
            try {
                $postData = $request->getParsedBody();
                $row['short_url'] = trim($postData['short_url'] ?? '', '/');
                $row['long_url'] = $postData['long_url'] ?? null;

                if (empty($row['short_url']) || empty($row['long_url'])) {
                    throw new \InvalidArgumentException('Short and Long URL are required.');
                }

                $this->db->insert(
                    'web_short_urls',
                    [
                        'short_url' => $row['short_url'],
                        'long_url' => $row['long_url'],
                        'creator' => $currentUser['id'],
                    ]
                );

                $request->getFlash()->success('Short URL created.');

                return $response->withRedirect(
                    $request->getRouter()->urlFor('dashboard:short_urls')
                );
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }

        return $request->getView()->renderToResponse(
            $response,
            'dashboard/short_urls/edit',
            [
                'isEditMode' => false,
                'row' => $row,
                'error' => $error,
            ]
        );
    }

    public function editAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $id = $params['id'] ?? $request->getParam('id');
        if (empty($id)) {
            throw new \InvalidArgumentException('ID is required.');
        }

        $row = $this->db->fetchAssociative(
            <<<'SQL'
                SELECT *
                FROM web_short_urls
                WHERE id = :id
            SQL,
            [
                'id' => $id,
            ]
        );

        if ($row === false) {
            throw new \InvalidArgumentException('Record not found!');
        }

        $error = null;

        if ($request->isPost()) {
            try {
                $postData = $request->getParsedBody();
                $row['short_url'] = trim($postData['short_url'] ?? '', '/');
                $row['long_url'] = $postData['long_url'] ?? null;

                if (empty($row['short_url']) || empty($row['long_url'])) {
                    throw new \InvalidArgumentException('Short and Long URL are required.');
                }

                $this->db->update(
                    'web_short_urls',
                    [
                        'short_url' => $row['short_url'],
                        'long_url' => $row['long_url'],
                    ],
                    [
                        'id' => $row['id'],
                    ]
                );

                $request->getFlash()->success('Short URL updated.');

                return $response->withRedirect(
                    $request->getRouter()->urlFor('dashboard:short_urls')
                );
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }

        return $request->getView()->renderToResponse(
            $response,
            'dashboard/short_urls/edit',
            [
                'isEditMode' => true,
                'row' => $row,
                'error' => $error,
            ]
        );
    }

    public function deleteAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $id = $params['id'] ?? $request->getParam('id');
        if (empty($id)) {
            throw new \InvalidArgumentException('ID is required.');
        }

        $this->db->delete(
            'web_short_urls',
            [
                'id' => $id,
            ]
        );

        $request->getFlash()->success('Short URL deleted.');

        return $response->withRedirect(
            $request->getRouter()->urlFor('dashboard:short_urls')
        );
    }
}
