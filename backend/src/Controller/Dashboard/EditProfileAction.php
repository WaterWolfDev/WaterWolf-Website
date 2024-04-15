<?php

namespace App\Controller\Dashboard;

use App\Exception\NotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\VrcApi;
use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;

final readonly class EditProfileAction
{
    public function __construct(
        private Connection $db,
        private VrcApi $vrcApi
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $currentUser = $request->getCurrentUser();
        assert($currentUser !== null);

        $editUserId = $params['id'] ?? $request->getParam('id');

        if (!empty($editUserId) && $currentUser->isAdmin()) {
            $isAdminMode = true;

            $groupsRaw = $this->db->fetchAllAssociative(
                <<<'SQL'
                    SELECT g.id, g.name
                    FROM web_groups AS g
                SQL
            );
            $groups = array_column($groupsRaw, 'name', 'id');
        } else {
            $isAdminMode = false;
            $editUserId = $currentUser['id'];

            $groups = [];
        }

        $profile = $this->db->fetchAssociative(
            <<<'SQL'
                SELECT *
                FROM web_users
                WHERE id=:id
            SQL,
            [
                'id' => $editUserId,
            ]
        );

        if ($profile === false) {
            throw NotFoundException::user($request);
        }

        $userGroupsRaw = $this->db->fetchAllAssociative(
            <<<'SQL'
                SELECT uhg.group_id
                FROM web_user_has_group AS uhg
                WHERE uhg.user_id = :id
            SQL,
            [
                'id' => $editUserId,
            ]
        );
        $userGroups = array_column($userGroupsRaw, 'group_id', 'group_id');

        $error = null;

        if ($request->isPost()) {
            try {
                $postData = $request->getParsedBody();

                $updateFields = [
                    'username' => $postData['username'],
                    'email' => $postData['email'],
                    'discord' => $postData['discord'] ?? null,
                    'twitch' => $postData['twitch'] ?? null,
                    'vrchat_uid' => $postData['vrchat_uid'] ?? null,
                    'vrcdn' => $postData['vrcdn'] ?? null,
                    'website' => $postData['website'] ?? null,
                    'aboutme' => $postData['aboutme'] ?? null,
                    'pronouns' => $postData['pronouns'] ?? null,
                    'dj_name' => $postData['dj_name'] ?? null,
                    'dj_genre' => $postData['dj_genre'] ?? null,
                ];

                if ($updateFields['email'] !== $profile['email']) {
                    if (empty($updateFields['email'])) {
                        throw new \InvalidArgumentException('E-mail address is required.');
                    }

                    // Check if the new e-mail is a duplicate.
                    $checkEmail = $this->db->fetchOne(
                        <<<'SQL'
                            SELECT username
                            FROM web_users
                            WHERE LOWER(email) = LOWER(:email)
                            AND id != :id
                        SQL,
                        [
                            'email' => $updateFields['email'],
                            'id' => $editUserId,
                        ]
                    );

                    if ($checkEmail !== false) {
                        throw new \InvalidArgumentException('E-mail address is already in use by another user.');
                    }
                }

                if ($updateFields['username'] !== $profile['username']) {
                    if (empty($updateFields['username'])) {
                        throw new \InvalidArgumentException('Username is required.');
                    }

                    // Check if the new username is a duplicate.
                    $checkUsername = $this->db->fetchOne(
                        <<<'SQL'
                            SELECT username
                            FROM web_users
                            WHERE LOWER(username) = LOWER(:username)
                            AND id != :id
                        SQL,
                        [
                            'username' => $updateFields['username'],
                            'id' => $editUserId,
                        ]
                    );

                    if ($checkUsername !== false) {
                        throw new \InvalidArgumentException('Username is already in use by another user.');
                    }
                }

                // Handle VRC UID change.
                if ($updateFields['vrchat_uid'] !== $profile['vrchat_uid']) {
                    $updateFields['vrchat_uid'] = VrcApi::parseUserId($updateFields['vrchat_uid']);
                    $updateFields['vrchat'] = $this->vrcApi->getDisplayNameFromUid($updateFields['vrchat_uid']);
                    $updateFields['vrchat_synced_at'] = time();
                }

                if ($profile['is_team'] === 1) {
                    $updateFields['title'] = $postData['title'] ?? null;
                }

                if ($isAdminMode) {
                    $updateFields = [
                        ...$updateFields,
                        'banned' => $postData['banned'] ?? 0,
                        'is_team' => $postData['is_team'] ?? 0,
                        'is_admin' => $postData['is_admin'] ?? 0,
                        'is_mod' => $postData['is_mod'] ?? 0,
                        'is_dj' => $postData['is_dj'] ?? 0,
                    ];

                    $this->db->delete(
                        'web_user_has_group',
                        [
                            'user_id' => $editUserId,
                        ]
                    );

                    foreach ((array)($postData['groups'] ?? []) as $groupId) {
                        if (isset($groups[$groupId])) {
                            $this->db->insert(
                                'web_user_has_group',
                                [
                                    'user_id' => $editUserId,
                                    'group_id' => $groupId,
                                ]
                            );
                        }
                    }
                }

                $this->db->update(
                    'web_users',
                    $updateFields,
                    [
                        'id' => $editUserId,
                    ]
                );

                $request->getFlash()->success('Profile updated!');

                return $response->withRedirect(
                    (string)$request->getUri()
                );
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }

        return $request->getView()->renderToResponse(
            $response,
            'dashboard/profile',
            [
                'profile' => $profile,
                'isAdminMode' => $isAdminMode,
                'groups' => $groups,
                'userGroups' => $userGroups,
                'error' => $error,
            ]
        );
    }
}
