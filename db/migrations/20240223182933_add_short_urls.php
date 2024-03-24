<?php

declare(strict_types=1);

use Phinx\Db\Table\Column;
use Phinx\Migration\AbstractMigration;

final class AddShortUrls extends AbstractMigration
{
    /**
     * Add short URL table
     */
    public function change(): void
    {
        $this->table('web_short_urls')
            ->addColumn(
                (new Column())
                    ->setName('creator')
                    ->setType(Column::INTEGER)
                    ->setNull(true)
            )->addColumn(
                (new Column())
                    ->setName('short_url')
                    ->setType(Column::STRING)
                    ->setLimit(128)
                    ->setNull(false)
            )->addColumn(
                (new Column())
                    ->setName('long_url')
                    ->setType(Column::STRING)
                    ->setLimit(255)
                    ->setNull(false)
            )->addColumn(
                (new Column())
                    ->setName('views')
                    ->setType(Column::INTEGER)
                    ->setNull(false)
                    ->setDefault(0)
            )->addTimestamps(null, false)
            ->addForeignKey(
                'creator',
                'web_users',
                'id',
                [
                    'delete' => 'SET NULL',
                    'update' => 'CASCADE',
                ]
            )->addIndex(
                'short_url',
                [
                    'unique' => true
                ]
            )->create();
    }
}
