<?php

namespace App\Console\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('init', 'Initialize the system upon container startup.')]
final class InitCommand extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Initialization');

        $uptimeRet = $this->runCommand($output, 'uptime-wait');
        if ($uptimeRet !== 0) {
            return $uptimeRet;
        }

        $this->runCommand($output, 'migrate');

        return 0;
    }
}
