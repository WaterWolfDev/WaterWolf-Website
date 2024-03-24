<?php

namespace App\Console\Command;

use App\Environment;
use Phinx\Console\PhinxApplication;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('migrate', 'Update the database to the latest migration version.')]
final class MigrateCommand extends AbstractCommand
{
    public function __construct(
        private readonly Environment $environment
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $phinx = new PhinxApplication();
        $command = $phinx->find('migrate');

        $arguments = [
            'command' => 'migrate',
            '--environment' => 'db',
            '--configuration' => $this->environment->getBaseDirectory() . '/phinx.php',
        ];

        return $command->run(new ArrayInput($arguments), $output);
    }
}
