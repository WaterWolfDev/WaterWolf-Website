<?php

declare(strict_types=1);

namespace App\Console\Command;

use App\Environment;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'backup',
    description: 'Store a backup of the current database.',
)]
final class BackupCommand extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Backup Database');

        $tempDir = Environment::getTempDirectory() . '/db';

        $fsUtils = new Filesystem();
        $fsUtils->remove($tempDir);
        $fsUtils->mkdir($tempDir);

        $path = $tempDir . '/db.sql';

        $this->dumpDatabase($io, $path);

        if (Environment::isProduction()) {
            $this->passThruProcess(
                $io,
                'restic --verbose backup ' . $tempDir,
                $tempDir
            );
        }

        $io->success('DB Backup Complete: File available at ' . $path);
        return 0;
    }

    protected function dumpDatabase(
        SymfonyStyle $io,
        string $path
    ): void {
        $connSettings = Environment::getDatabaseInfo();
        $commandEnvVars = [
            'DB_HOST' => $connSettings['host'],
            'DB_DATABASE' => $connSettings['dbname'],
            'DB_USERNAME' => $connSettings['user'],
            'DB_PASSWORD' => $connSettings['password'],
            'DB_DEST' => $path,
        ];

        $commandFlags = [
            '--host=$DB_HOST',
            '--user=$DB_USERNAME',
            '--password=$DB_PASSWORD',
            '--add-drop-table',
            '--default-character-set=UTF8MB4',
        ];

        $this->passThruProcess(
            $io,
            'mariadb-dump ' . implode(' ', $commandFlags) . ' $DB_DATABASE > $DB_DEST',
            dirname($path),
            $commandEnvVars
        );
    }

    protected function passThruProcess(
        SymfonyStyle $io,
        string|array $cmd,
        ?string $cwd = null,
        array $env = [],
        int $timeout = 14400
    ): Process {
        set_time_limit($timeout);

        if (is_array($cmd)) {
            $process = new Process($cmd, $cwd);
        } else {
            $process = Process::fromShellCommandline($cmd, $cwd);
        }

        $process->setTimeout($timeout - 60);
        $process->setIdleTimeout(null);

        $stdout = [];
        $stderr = [];

        $process->mustRun(function ($type, $data) use ($process, $io, &$stdout, &$stderr): void {
            if ($process::ERR === $type) {
                $io->getErrorStyle()->write($data);
                $stderr[] = $data;
            } else {
                $io->write($data);
                $stdout[] = $data;
            }
        }, $env);

        return $process;
    }
}
