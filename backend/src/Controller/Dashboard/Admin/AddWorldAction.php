<?php

namespace App\Controller\Dashboard\Admin;

use App\Http\Response;
use App\Http\ServerRequest;
use App\Media;
use App\Service\VrcApi;
use Doctrine\DBAL\Connection;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Query;
use GuzzleHttp\Psr7\Uri;
use Intervention\Image\ImageManager;
use Psr\Http\Message\ResponseInterface;

final readonly class AddWorldAction
{
    private Client $vrcApiClient;

    public function __construct(
        private Connection $db,
        private ImageManager $imageManager,
        private Client $httpClient,
        VrcApi $vrcApi
    ) {
        $this->vrcApiClient = $vrcApi->getHttpClient();
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $currentUser = $request->getCurrentUser();
        assert($currentUser !== null);

        $error = null;

        if ($request->isPost()) {
            try {
                $worldId = $request->getParam('id');
                if (empty($worldId)) {
                    throw new \InvalidArgumentException('World ID not specified.');
                }

                $worldId = $this->parseWorldId($worldId);

                // Fetch world info from the VRC API.
                $worldInfo = VrcApi::processResponse(
                    $this->vrcApiClient->get(sprintf('worlds/%s', $worldId))
                );

                // Pull the world image
                $imageUrl = $worldInfo['imageUrl'];
                $imageData = $this->httpClient->get($imageUrl)->getBody()->getContents();

                $imageRelativePath = Media::worldPath($worldId . '.png');

                $image = $this->imageManager->read($imageData);

                $fs = Media::getFilesystem();
                $fs->write($imageRelativePath, $image->encodeByPath($imageRelativePath)->toString());

                // Add the DB record
                $this->db->insert(
                    'web_worlds',
                    [
                        'title' => $worldInfo['name'],
                        'creator' => $currentUser['id'],
                        'image' => $imageRelativePath,
                        'description' => $worldInfo['description'],
                        'world_id' => $worldId,
                        'world_creator' => $worldInfo['authorName'],
                    ]
                );

                $worldDbId = $this->db->lastInsertId();

                $request->getFlash()->success('World successfully imported!');

                return $response->withRedirect(
                    $request->getRouter()->urlFor('world', ['id' => $worldDbId])
                );
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }

        return $request->getView()->renderToResponse(
            $response,
            'dashboard/admin/add_world',
            [
                'error' => $error,
            ]
        );
    }

    private function parseWorldId(string $worldId): string
    {
        $worldId = trim($worldId);

        if (str_starts_with($worldId, 'wrld')) {
            return $worldId;
        }

        if (str_starts_with($worldId, 'http')) {
            $uri = new Uri($worldId);

            // URLs in the form of:
            // https://vrchat.com/home/world/wrld_bcfd94c8-3d69-4d9b-b610-282c6d8a5b3d
            if (str_starts_with($uri->getPath(), '/home/world')) {
                $uriParts = explode('/', trim($uri->getPath(), '/'));

                if (str_starts_with($uriParts[2], 'wrld')) {
                    return $uriParts[2];
                }
            }

            // URLs in the form of:
            // https://vrchat.com/home/launch?worldId=wrld_4cf554b4-430c-4f8f-b53e-1f294eed230b&...
            $queryParams = Query::parse($uri->getQuery());

            if (isset($queryParams['worldId']) && str_starts_with($queryParams['worldId'], 'wrld')) {
                return $queryParams['worldId'];
            }
        }

        throw new \InvalidArgumentException('Could not determine world ID from URL.');
    }
}
