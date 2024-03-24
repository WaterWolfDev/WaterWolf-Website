<?php

namespace App\Console\Command;

use App\Environment;
use Doctrine\DBAL\Connection;
use Phinx\Console\PhinxApplication;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('seed', 'Populate the database with placeholder values (for development).')]
final class SeedCommand extends AbstractCommand
{
    public function __construct(
        private readonly Environment $environment,
        private readonly Connection $db
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->environment->isDev()) {
            $io->error('This can only be used in development mode.');
            return 1;
        }

        $userCount = $this->db->fetchOne(
            <<<'SQL'
                SELECT COUNT(*)
                FROM web_users
            SQL
        );

        if ($userCount > 0) {
            $io->warning('Cannot pre-populate database: database already seeded!');
            return 1;
        }

        $phinx = new PhinxApplication();
        $command = $phinx->find('seed:run');

        $arguments = [
            'command' => 'seed:run',
            '--environment' => 'db',
            '--configuration' => $this->environment->getBaseDirectory() . '/phinx.php',
        ];

        return $command->run(new ArrayInput($arguments), $output);
    }
}
