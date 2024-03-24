<?php

declare(strict_types=1);

use Phinx\Db\Table\Column;
use Phinx\Migration\AbstractMigration;

final class ExpandPosterNetwork extends AbstractMigration
{
    /**
     * Expand poster network.
     */
    public function change(): void
    {
        $groupsTable = $this->table('web_groups', [
            'id' => false,
            'primary_key' => 'id'
        ]);

        $groupsTable->addColumn(
                (new Column())
                    ->setName('id')
                    ->setType(Column::STRING)
                    ->setLimit(50)
                    ->setNull(false)
            )->addColumn(
                (new Column())
                    ->setName('name')
                    ->setType(Column::STRING)
                    ->setLimit(255)
                    ->setNull(false)
            )->create();

        $this->table('web_user_has_group', [
                'id' => false,
                'primary_key' => ['user_id', 'group_id']
            ])->addColumn(
                (new Column())
                    ->setName('user_id')
                    ->setType(Column::INTEGER)
                    ->setNull(false)
            )->addColumn(
                (new Column())
                    ->setName('group_id')
                    ->setType(Column::STRING)
                    ->setLimit(50)
                    ->setNull(false)
            )->addForeignKey(
                'user_id',
                'web_users',
                'id',
                [
                    'delete' => 'CASCADE',
                    'update' => 'CASCADE',
                ]
            )->addForeignKey(
                'group_id',
                'web_groups',
                'id',
                [
                    'delete' => 'CASCADE',
                    'update' => 'CASCADE',
                ]
            )->create();

        $posterTypesTable = $this->table('web_poster_types', [
            'id' => false,
            'primary_key' => ['id']
        ]);

        $posterTypesTable->addColumn(
                (new Column())
                    ->setName('id')
                    ->setType(Column::STRING)
                    ->setLimit(50)
                    ->setNull(false)
            )->addColumn(
                (new Column())
                    ->setName('description')
                    ->setType(Column::STRING)
                    ->setLimit(255)
                    ->setNull(true)
            )->create();

        $this->table('web_posters')
            ->removeColumn('location')
            ->removeColumn('mode')
            ->addColumn(
                (new Column())
                    ->setName('nickname')
                    ->setType(Column::STRING)
                    ->setLimit(255)
                    ->setNull(true)
            )
            ->addColumn(
                (new Column())
                    ->setName('expires_at')
                    ->setType(Column::TIMESTAMP)
                    ->setNull(true)
            )
            ->addColumn(
                (new Column())
                    ->setName('type_id')
                    ->setType(Column::STRING)
                    ->setLimit(50)
                    ->setNull(true)
            )->addColumn(
                (new Column())
                    ->setName('group_id')
                    ->setType(Column::STRING)
                    ->setLimit(50)
                    ->setNull(true)
            )->addForeignKey(
                'type_id',
                'web_poster_types',
                'id',
                [
                    'delete' => 'SET NULL',
                    'update' => 'CASCADE',
                ]
            )->addForeignKey(
                'group_id',
                'web_groups',
                'id',
                [
                    'delete' => 'SET NULL',
                    'update' => 'CASCADE',
                ]
            )->update();
    }
}
