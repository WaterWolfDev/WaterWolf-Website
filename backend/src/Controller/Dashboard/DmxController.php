<?php

namespace App\Controller\Dashboard;

use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;

final readonly class DmxController
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

        $isTeam = $currentUser->isTeam() || $currentUser->isMod();
        $editRow = null;

        if ($isTeam && $request->isPost()) {
            $postData = $request->getParams();

            // Insert
            if (isset($postData['add_fixture'])) {
                $this->db->insert(
                    'web_dmx_fixtures',
                    [
                        'fixture_name' => $postData['fixture_name'] ?? null,
                        'universe_number' => $postData['universe_number'] ?? null,
                        'channel_number' => $postData['channel_number'] ?? null,
                        'rig_name' => $postData['rig_name'] ?? null,
                    ]
                );
            }

            // Update
            if (isset($postData['update_id'])) {
                $this->db->update(
                    'web_dmx_fixtures',
                    [
                        'fixture_name' => $postData['update_fixture_name'] ?? null,
                        'universe_number' => $postData['update_universe_number'] ?? null,
                        'channel_number' => $postData['update_channel_number'] ?? null,
                        'rig_name' => $postData['update_rig_name'] ?? null,
                    ],
                    [
                        'id' => $postData['update_id'],
                    ]
                );
            }

            // Delete
            if (isset($postData['delete_id'])) {
                $this->db->delete(
                    'web_dmx_fixtures',
                    [
                        'id' => $postData['delete_id'],
                    ]
                );
            }

            if (isset($postData['edit_id'])) {
                $editRow = $this->db->fetchAssociative(
                    <<<'SQL'
                        SELECT *
                        FROM web_dmx_fixtures
                        WHERE id = :id
                    SQL,
                    [
                        'id' => $postData['edit_id'],
                    ]
                );
            }
        }

        $selectedRigName = $request->getParam('rig_name');

        if (!empty($selectedRigName)) {
            $fixtures = $this->db->fetchAllAssociative(
                <<<'SQL'
                    SELECT *
                    FROM web_dmx_fixtures
                    WHERE rig_name = :name
                SQL,
                [
                    'name' => $selectedRigName,
                ]
            );
        } else {
            $fixtures = $this->db->fetchAllAssociative(
                <<<'SQL'
                    SELECT *
                    FROM web_dmx_fixtures
                SQL
            );
        }

        $rigLookupRaw = $this->db->fetchAllAssociative(
            <<<'SQL'
                SELECT DISTINCT rig_name
                FROM web_dmx_fixtures
            SQL
        );
        $rigLookup = array_column($rigLookupRaw, 'rig_name');

        return $request->getView()->renderToResponse(
            $response,
            'dashboard/dmx',
            [
                'isTeam' => $isTeam,
                'editRow' => $editRow,
                'fixtures' => $fixtures,
                'rigLookup' => $rigLookup,
                'selectedRigName' => $selectedRigName,
            ]
        );
    }
}
