<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ActuallyDropExtraTables extends AbstractMigration
{
    /**
     * Ensure tables are dropped that weren't correctly dropped in an earlier migration.
     */
    public function change(): void
    {
        $dropTables = ['web_banlist', 'web_events'];
        foreach($dropTables as $table) {
            if ($this->hasTable($table)) {
                $this->table($table)->drop()->save();
            }
        }
    }
}
