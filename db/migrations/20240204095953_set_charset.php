<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class SetCharset extends AbstractMigration
{
    /*
     * Set the charset and collation for all columns in all tables.
     */
    public function change(): void
    {
        if ($this->isMigratingUp()) {
            $initialDbSql = file_get_contents(__DIR__ . '/20240204095953_set_charset.sql');
            $this->execute($initialDbSql);
        }
    }
}
