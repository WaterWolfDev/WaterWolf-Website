<?php

namespace App\Controller\Dashboard;

use App\Http\Response;
use App\Http\ServerRequest;
use App\Media;
use Doctrine\DBAL\Connection;
use GuzzleHttp\Psr7\UploadedFile;
use Intervention\Image\ImageManager;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Filesystem\Filesystem;

final readonly class AvatarAction
{
    public function __construct(
        private Connection $db,
        private ImageManager $imageManager,
        private Media $media,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $currentUser = $request->getCurrentUser();
        assert($currentUser !== null);

        $fs = $this->media->getFilesystem();

        $type = $params['type'] ?? $request->getParam('type', 'avatar');
        $avatarField = match ($type) {
            'dj' => 'dj_img',
            default => 'user_img'
        };

        $uploadFolder = match ($type) {
            'dj' => '/img/djs',
            default => '/img/profile'
        };

        if ($request->isPost()) {
            $files = $request->getUploadedFiles();

            if (empty($files['file'])) {
                throw new \InvalidArgumentException('File not uploaded!');
            }

            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $files['file'];

            // Remove existing image if it's set.
            if (!empty($currentUser[$avatarField])) {
                $currentImage = $uploadFolder . '/' . $currentUser[$avatarField];
                if ($fs->has($currentImage)) {
                    $fs->delete($currentImage);
                }
            }

            $imageFileType = strtolower(pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION));
            $imageBasename = $currentUser['id'] . '-' . time() . '.' . $imageFileType;
            $targetFile = $uploadFolder . '/' . $imageBasename;

            // Resize the uploaded image and save it to the destination location.
            $image = $this->imageManager->read($uploadedFile->getStream()->getContents());
            $image->cover(332, 364);

            $fs->write($targetFile, $image->encodeByPath($targetFile)->toString());

            // Set the user's image location to the new image.
            $this->db->update(
                'web_users',
                [
                    $avatarField => $imageBasename,
                ],
                [
                    'id' => $currentUser['id'],
                ]
            );

            $request->getFlash()->success('Avatar updated!');

            return $response->withRedirect(
                $request->getRouter()->urlFor('dashboard:profile')
            );
        }

        return $request->getView()->renderToResponse(
            $response,
            'dashboard/avatar',
            [
                'type' => $type,
            ]
        );
    }
}
