<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RenamePosterNickname extends AbstractMigration
{
    /**
     * Rename the posters "nickname" to "collection".
     */
    public function change(): void
    {
        $this->table('web_posters')
            ->renameColumn('nickname', 'collection')
            ->update();
    }
}
