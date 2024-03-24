<?php

namespace App\Console\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('sync', 'Run the routine synchronization (cron) tasks.')]
final class SyncCommand extends AbstractCommand
{
    public function __construct(
        private readonly Connection $db
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->info('Starting sync tasks...');

        $this->clearUserLoginTokens($io);

        $io->success('Sync tasks completed.');
        return 0;
    }

    private function clearUserLoginTokens(SymfonyStyle $io): void
    {
        $thresholdDate = new \DateTimeImmutable('-2 days', new \DateTimeZone('UTC'));
        $this->db->executeQuery(
            <<<'SQL'
                DELETE FROM web_user_login_tokens
                WHERE created_at < :threshold
            SQL,
            [
                'threshold' => $thresholdDate->format('Y-m-d H:i:s'),
            ]
        );
    }
}
