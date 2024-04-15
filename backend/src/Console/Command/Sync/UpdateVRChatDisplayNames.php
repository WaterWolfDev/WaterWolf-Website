<?php

namespace App\Console\Command\Sync;

use App\Console\Command\AbstractCommand;
use App\Service\VrcApi;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('sync:update-vrchat-names', 'Sync task: Update VRChat display names.')]
final class UpdateVRChatDisplayNames extends AbstractCommand
{
    public function __construct(
        private readonly Connection $db,
        private readonly VrcApi $vrcApi
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->info('Updating VRChat display names...');

        // Legacy: Try to fetch UIDs for users with only display name and no UID.
        $legacyUsers = $this->db->fetchAllAssociative(
            <<<'SQL'
                SELECT *
                FROM web_users 
                WHERE vrchat_synced_at = 0
                AND vrchat_uid IS NULL
                AND vrchat IS NOT NULL
                LIMIT 10
            SQL
        );

        foreach ($legacyUsers as $row) {
            $displayName = trim($row['vrchat']);
            $updated = [
                'vrchat_synced_at' => time(),
                'vrchat_uid' => null,
            ];

            if (!empty($displayName)) {
                // Search for a user with this display name.
                $searchResults = VrcApi::processResponse(
                    $this->vrcApi->getHttpClient()->get(
                        'users',
                        [
                            'query' => [
                                'search' => $displayName,
                                'n' => 1,
                            ],
                        ]
                    )
                );

                if (count($searchResults) === 1) {
                    $userRow = $searchResults[0];

                    if (mb_strtolower($userRow['displayName']) === mb_strtolower($displayName)) {
                        $updated['vrchat_uid'] = $userRow['id'];
                    }
                }
            }

            $this->db->update(
                'web_users',
                $updated,
                ['id' => $row['id']]
            );
        }

        // Periodically update users who have UIDs specified in their profile.
        $thresholdDate = new \DateTimeImmutable('-1 week', new \DateTimeZone('UTC'));

        $usersToUpdate = $this->db->fetchAllAssociative(
            <<<'SQL'
                SELECT *
                FROM web_users 
                WHERE vrchat_uid IS NOT NULL
                AND vrchat_synced_at < :threshold
                LIMIT 10
            SQL,
            [
                'threshold' => $thresholdDate->getTimestamp(),
            ]
        );

        foreach ($usersToUpdate as $row) {
            $updated = [
                'vrchat_synced_at' => time(),
                'vrchat' => $row['vrchat'],
            ];

            $uid = trim($row['vrchat_uid']);
            if (!empty($uid)) {
                try {
                    $row['vrchat'] = $this->vrcApi->getDisplayNameFromUid($uid);
                } catch (\Throwable) {
                }
            }

            $this->db->update(
                'web_users',
                $updated,
                ['id' => $row['id']]
            );
        }

        $io->success('Task completed.');
        return 0;
    }
}
