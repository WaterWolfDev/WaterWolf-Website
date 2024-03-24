<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RemoveSessionTables extends AbstractMigration
{
    /**
     * Remove session tables.
     */
    public function change(): void
    {
        $this->table('web_world_views')
            ->drop()
            ->save();

        $this->table('web_sessions')
            ->drop()
            ->save();
    }
}
