<?php

namespace App\Console\Command;

use App\Environment;
use Doctrine\DBAL\DriverManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('uptime-wait', 'Wait for critical services (i.e. database) to be started up before continuing.')]
final class UptimeWaitCommand extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->info('Starting services...');

        $elapsed = 0;
        $timeout = 180;

        $connectionParams = [
            'driver' => 'pdo_mysql',
            ...Environment::getDatabaseInfo(),
        ];

        while ($elapsed <= $timeout) {
            try {
                $conn = DriverManager::getConnection($connectionParams);
                $pdo = $conn->getNativeConnection();

                assert($pdo instanceof \PDO);

                $pdo->exec('SELECT 1');

                $io->success('Services started up and ready!');
                return 0;
            } catch (\Throwable $e) {
                sleep(1);
                $elapsed += 1;

                $io->writeln($e->getMessage());
            }
        }

        $io->error('Timed out waiting for services to start.');
        return 1;
    }
}
