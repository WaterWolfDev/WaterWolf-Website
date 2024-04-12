<?php

declare(strict_types=1);

use Phinx\Db\Table\Column;
use Phinx\Migration\AbstractMigration;

final class AddVRCSyncFields extends AbstractMigration
{
    public function change(): void
    {
        $this->table('web_users')
            ->addColumn(
                (new Column())
                    ->setName('vrchat_uid')
                    ->setType(Column::STRING)
                    ->setLimit(200)
                    ->setNull(true)
                    ->setAfter('vrchat')
            )->addColumn(
                (new Column())
                    ->setName('vrchat_synced_at')
                    ->setType(Column::INTEGER)
                    ->setDefault(0)
                    ->setNull(false)
                    ->setAfter('vrchat_uid')
            )->update();
    }
}
