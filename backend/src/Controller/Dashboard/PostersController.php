<?php

namespace App\Controller\Dashboard;

use App\Exception\NotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Media;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Intervention\Image\ImageManager;
use League\Flysystem\UnableToDeleteFile;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;

final readonly class PostersController
{
    public function __construct(
        private Connection $db,
        private ImageManager $imageManager
    ) {
    }

    public function listAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $currentUser = $request->getCurrentUser();
        assert($currentUser !== null);

        $types = $this->getTypes();

        $qb = $this->db->createQueryBuilder()
            ->select('p.*, u.username')
            ->from('web_posters', 'p')
            ->join('p', 'web_users', 'u', 'p.creator = u.id')
            ->where('u.banned != 1')
            ->orderBy('p.id DESC');

        $groupLookup = $this->getEditableGroups($request);

        if (!$currentUser->isMod()) {
            $qb->andWhere('(p.group_id IS NULL) OR (p.group_id IN (:groups))')
                ->setParameter('user_id', $currentUser['id'])
                ->setParameter('groups', array_keys($groupLookup), ArrayParameterType::STRING);
        }

        $groups = [
            '_mine' => [
                'name' => 'My Posters',
                'canEdit' => true,
                'posters' => [],
            ],
            '_community' => [
                'name' => 'Community Posters',
                'canEdit' => $currentUser->isMod(),
                'posters' => [],
            ],
        ];

        foreach ($groupLookup as $groupId => $groupName) {
            $groups[$groupId] = [
                'name' => $groupName,
                'code' => $groupId,
                'canEdit' => true,
                'posters' => [],
            ];
        }

        $nowDt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        foreach ($qb->fetchAllAssociative() as $poster) {
            // Calculate poster expiration.
            if ($poster['expires_at']) {
                $expiresAt = new \DateTimeImmutable($poster['expires_at'], new \DateTimeZone('UTC'));

                $poster['isExpired'] = $expiresAt < $nowDt;
                $poster['expiresAtText'] = $expiresAt->format('F j, Y g:ia');
            } else {
                $poster['isExpired'] = false;
                $poster['expiresAtText'] = null;
            }

            // Assign poster to the correct group
            if (!empty($poster['group_id'])) {
                $groups[$poster['group_id']]['posters'][] = $poster;
            } elseif ($poster['creator'] === $currentUser['id']) {
                $groups['_mine']['posters'][] = $poster;
            } else {
                $groups['_community']['posters'][] = $poster;
            }
        }

        return $request->getView()->renderToResponse(
            $response,
            'dashboard/posters/list',
            [
                'types' => $types,
                'groups' => $groups,
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

        $groups = $this->getEditableGroups($request);
        $types = $this->getTypes();

        if ($request->isPost()) {
            try {
                $files = $request->getUploadedFiles();
                if (empty($files['fileToUpload'])) {
                    throw new \InvalidArgumentException('No poster uploaded.');
                }

                /** @var UploadedFileInterface $file */
                $file = $files['fileToUpload'];

                $row = $this->generateAssets($file);
                $row['creator'] = $currentUser['id'];

                $postData = $request->getParsedBody();

                $inputType = $postData['type'] ?? null;
                if (!empty($inputType) && isset($types[$inputType])) {
                    $row['type_id'] = $inputType;
                }

                $inputGroup = $postData['group'] ?? null;
                if (!empty($inputGroup) && isset($groups[$inputGroup])) {
                    $row['group_id'] = $inputGroup;
                }

                $inputCollection = $postData['collection'] ?? null;
                if (!empty($inputCollection)) {
                    $row['collection'] = $inputCollection;
                }

                $inputExpiresAt = $postData['expires_at'] ?? null;
                if (!empty($inputExpiresAt)) {
                    $dt = new \DateTimeImmutable($inputExpiresAt, new \DateTimeZone('UTC'));
                    $row['expires_at'] = $dt->format('Y-m-d H:i:s');
                }

                $this->db->insert('web_posters', $row);

                $request->getFlash()->success('Poster uploaded.');
                return $response->withRedirect(
                    $request->getRouter()->urlFor('dashboard:posters')
                );
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }

        return $request->getView()->renderToResponse(
            $response,
            'dashboard/posters/edit',
            [
                'isEditMode' => false,
                'row' => $row,
                'error' => $error,
                'groups' => $groups,
                'types' => $types,
            ]
        );
    }

    public function editAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $id = $params['id'] ?? $request->getParam('pid');
        $row = $this->getEditablePoster($request, $id);

        $groups = $this->getEditableGroups($request);
        $types = $this->getTypes();

        $error = null;

        if ($request->isPost()) {
            try {
                $files = $request->getUploadedFiles();
                if (isset($files['fileToUpload'])) {
                    /** @var UploadedFileInterface $file */
                    $file = $files['fileToUpload'];

                    if ($file->getError() === UPLOAD_ERR_OK) {
                        $this->deleteAssets($row);

                        $row = [
                            ...$row,
                            ...$this->generateAssets($files['fileToUpload']),
                        ];
                    }
                }

                $postData = $request->getParsedBody();

                $inputType = $postData['type'] ?? null;
                $row['type_id'] = (!empty($inputType) && isset($types[$inputType]))
                    ? $inputType
                    : null;

                $inputGroup = $postData['group'] ?? null;
                $row['group_id'] = (!empty($inputGroup) && isset($groups[$inputGroup]))
                    ? $inputGroup
                    : null;

                $inputCollection = $postData['collection'] ?? null;
                $row['collection'] = (!empty($inputCollection))
                    ? $inputCollection
                    : null;

                $inputExpiresAt = $postData['expires_at'] ?? null;
                if (!empty($inputExpiresAt)) {
                    $dt = new \DateTimeImmutable($inputExpiresAt, new \DateTimeZone('UTC'));
                    $row['expires_at'] = $dt->format('Y-m-d H:i:s');
                } else {
                    $row['expires_at'] = null;
                }

                $id = $row['id'];
                unset($row['id']);

                $this->db->update(
                    'web_posters',
                    $row,
                    [
                        'id' => $id,
                    ]
                );

                $request->getFlash()->success('Poster updated.');

                return $response->withRedirect(
                    $request->getRouter()->urlFor('dashboard:posters')
                );
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }

        return $request->getView()->renderToResponse(
            $response,
            'dashboard/posters/edit',
            [
                'isEditMode' => true,
                'row' => $row,
                'error' => $error,
                'groups' => $groups,
                'types' => $types,
            ]
        );
    }

    public function deleteAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $id = $params['id'] ?? $request->getParam('pid');
        $row = $this->getEditablePoster($request, $id);

        $this->deleteAssets($row);

        $this->db->delete(
            'web_posters',
            [
                'id' => $row['id'],
            ]
        );

        $request->getFlash()->success('Poster removed.');
        return $response->withRedirect(
            $request->getRouter()->urlFor('dashboard:posters')
        );
    }

    private function getEditableGroups(ServerRequest $request): array
    {
        $currentUser = $request->getCurrentUser();
        assert($currentUser !== null);

        if ($currentUser->isMod()) {
            $groupsRaw = $this->db->fetchAllAssociative(
                <<<'SQL'
                SELECT g.id, g.name
                FROM web_groups AS g
            SQL
            );
        } else {
            $groupsRaw = $this->db->fetchAllAssociative(
                <<<'SQL'
                    SELECT g.id, g.name
                    FROM web_groups AS g 
                    JOIN web_user_has_group AS uhg ON g.id = uhg.group_id
                    WHERE uhg.user_id = :id
                SQL,
                [
                    'id' => $currentUser['id'],
                ]
            );
        }

        return array_column($groupsRaw, 'name', 'id');
    }

    private function getTypes(): array
    {
        $typesRaw = $this->db->fetchAllAssociative(
            <<<'SQL'
                SELECT id, description
                FROM web_poster_types
            SQL
        );

        return array_column($typesRaw, 'description', 'id');
    }

    private function getEditablePoster(
        ServerRequest $request,
        int|null $id
    ): array {
        if ($id === null) {
            throw NotFoundException::poster($request);
        }

        $currentUser = $request->getCurrentUser();
        assert($currentUser !== null);

        $qb = $this->db->createQueryBuilder()
            ->select('p.*')
            ->from('web_posters', 'p')
            ->where('id = :id')
            ->setParameter('id', $id);

        if (!$currentUser->isMod()) {
            $groups = $this->getEditableGroups($request);

            $qb->andWhere('(p.group_id IS NULL AND p.creator = :user_id) OR (p.group_id IN (:groups))')
                ->setParameter('user_id', $currentUser['id'])
                ->setParameter('groups', array_keys($groups), ArrayParameterType::STRING);
        }

        $row = $qb->fetchAssociative();

        if ($row === false) {
            throw NotFoundException::poster($request);
        }

        return $row;
    }

    private function deleteAssets(array $poster): void
    {
        $fs = Media::getFilesystem();

        if (!empty($poster['full_path'])) {
            $fullPath = Media::posterPath($poster['full_path']);

            try {
                $fs->delete($fullPath);
            } catch (UnableToDeleteFile) {
                // Noop
            }
        }

        if (!empty($poster['thumb_path'])) {
            $thumbPath = Media::posterPath($poster['thumb_path']);

            try {
                $fs->delete($thumbPath);
            } catch (UnableToDeleteFile) {
                // Noop
            }
        }
    }

    /**
     * @return array{
     *     file: string,
     *     full_path: string,
     *     thumb_path: string
     * }
     */
    private function generateAssets(UploadedFileInterface $filePath): array
    {
        $image = $this->imageManager->read($filePath->getStream()->getContents());

        // Create a random 12 digit hash for file name
        $basename = bin2hex(random_bytes(6)); // 6 bytes = 12 hex characters

        $fullPath = $basename . '_full.jpg';
        $thumbPath = $basename . '_thumb.jpg';

        $sizes = [
            [118, 200, $thumbPath],
            [590, 1000, $fullPath],
        ];

        $fs = Media::getFilesystem();
        foreach ($sizes as [$width, $height, $filename]) {
            $thumbnail = clone $image;
            $thumbnail->cover($width, $height);

            $destPath = Media::posterPath($filename);
            $fs->write($destPath, $thumbnail->encodeByPath($destPath)->toString());
        }

        return [
            'file' => $basename,
            'full_path' => $fullPath,
            'thumb_path' => $thumbPath,
        ];
    }
}
