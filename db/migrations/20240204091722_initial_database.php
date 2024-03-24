<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class InitialDatabase extends AbstractMigration
{
    /**
     * Set up the initial database as of the start of migrations.
     */
    public function change(): void
    {
        if ($this->isMigratingUp()) {
            $initialDbSql = file_get_contents(__DIR__ . '/20240204091722_initial_database.sql');
            $this->execute($initialDbSql);
        }
    }
}
