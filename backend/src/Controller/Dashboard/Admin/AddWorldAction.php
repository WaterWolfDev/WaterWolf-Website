<?php

namespace App\Controller\Dashboard\Admin;

use App\Http\Response;
use App\Http\ServerRequest;
use App\Media;
use Doctrine\DBAL\Connection;
use GuzzleHttp\Client;
use Intervention\Image\ImageManager;
use Psr\Http\Message\ResponseInterface;

final readonly class AddWorldAction
{
    public function __construct(
        private Connection $db,
        private Client $http,
        private ImageManager $imageManager
    ) {
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

                // Get the URL from the form submission
                $url = 'https://vrchat.com/home/launch?worldId=' . $worldId;

                // Convert JSON data to an associative array
                $body = $this->http->get($url)
                    ->getBody()->getContents();

                // Regular expression pattern to find Twitter meta tags and their attributes
                $pattern = '/<meta\s+name="twitter:([^"]+)"\s+content="([^"]+)"/i';
                preg_match_all($pattern, $body, $matches);

                $worldData = [];
                foreach ($matches[1] as $index => $property) {
                    $content = $matches[2][$index];
                    $worldData[$property] = htmlspecialchars_decode($content);
                }

                $worldTitleFull = preg_replace('/[^\w\s.]/', '', substr($worldData['title'], 0, 255));
                $worldDescription = substr($worldData['description'], 0, 255);

                // Split the text using the word "by"
                $titleParts = explode(" by ", $worldTitleFull);

                $worldTitle = str_replace('  ', ' ', trim($titleParts[0]));
                $worldDbTitle = str_replace(' ', '_', $worldTitle);
                $worldCreator = trim($titleParts[1]);

                // Pull the world image
                $imageData = (str_starts_with($worldData['image'], 'http'))
                    ? $this->http->get($worldData['image'])->getBody()->getContents()
                    : $worldData['image'];

                $imageRelativePath = Media::worldPath($worldDbTitle . '.png');

                $image = $this->imageManager->read($imageData);

                $fs = Media::getFilesystem();
                $fs->write($imageRelativePath, $image->encodeByPath($imageRelativePath)->toString());

                // Add the DB record
                $this->db->insert(
                    'web_worlds',
                    [
                        'title' => $worldTitle,
                        'creator' => $currentUser['id'],
                        'image' => $imageRelativePath,
                        'description' => $worldDescription,
                        'world_id' => $worldId,
                        'world_creator' => $worldCreator,
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
}
