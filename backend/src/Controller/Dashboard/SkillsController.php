<?php

namespace App\Controller\Dashboard;

use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;

final readonly class SkillsController
{
    public function __construct(
        private Connection $db
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $currentUser = $request->getCurrentUser();
        assert($currentUser !== null);

        $userId = $currentUser['id'];

        $editRow = null;

        if ($request->isPost()) {
            $postData = $request->getParsedBody();

            if (isset($postData['skill'])) {
                $add_skill = $postData['skill'];

                $existingSkill = $this->db->fetchOne(
                    <<<'SQL'
                        SELECT COUNT(*)
                        FROM web_user_skills
                        WHERE skill = :skill
                        AND creator = :creator
                    SQL,
                    [
                        'skill' => $add_skill,
                        'creator' => $userId,
                    ]
                );

                if ($existingSkill == 0) {
                    $this->db->insert(
                        'web_user_skills',
                        [
                            'skill' => $add_skill,
                            'creator' => $userId,
                        ]
                    );
                }
            }

            if (isset($postData['delete_id'])) {
                $this->db->delete(
                    'web_user_skills',
                    [
                        'id' => $postData['delete_id'],
                        'creator' => $userId,
                    ]
                );
            }

            // Check for edit request
            if (isset($postData['edit_id'])) {
                $editRow = $this->db->fetchAssociative(
                    <<<'SQL'
                        SELECT *
                        FROM web_user_skills
                        WHERE id = :id AND creator = :creator
                    SQL,
                    [
                        'id' => $postData['edit_id'],
                        'creator' => $userId,
                    ]
                );
            }
        }

        $myTalents = $this->db->fetchAllAssociative(
            <<<'SQL'
                SELECT *
                FROM web_user_skills
                WHERE creator=:creator
                ORDER BY id DESC
            SQL,
            [
                'creator' => $userId,
            ]
        );

        $communityTalents = $this->db->fetchAllAssociative(
            <<<'SQL'
                SELECT skill, COUNT(*) as occurrences
                FROM web_user_skills
                GROUP BY skill
            SQL
        );

        // Build a lookup for the autocomplete.
        $skillLookup = [];
        foreach ($communityTalents as $row) {
            $skillLookup[] = $row['skill'];
        }

        return $request->getView()->renderToResponse(
            $response,
            'dashboard/skills',
            [
                'editRow' => $editRow,
                'myTalents' => $myTalents,
                'communityTalents' => $communityTalents,
                'skillLookup' => $skillLookup,
            ]
        );
    }
}
