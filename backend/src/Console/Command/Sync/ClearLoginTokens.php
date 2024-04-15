<?php

namespace App\Console\Command\Sync;

use App\Console\Command\AbstractCommand;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('sync:clear-login-tokens', 'Sync task: Clear login tokens.')]
final class ClearLoginTokens extends AbstractCommand
{
    public function __construct(
        private readonly Connection $db
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->info('Clearing login tokens...');

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

        $io->success('Task completed.');
        return 0;
    }
}
