<?php

declare(strict_types=1);

use Phinx\Db\Table\Column;
use Phinx\Migration\AbstractMigration;
use Phinx\Util\Literal;

final class AddUserLoginToken extends AbstractMigration
{
    /**
     * Add a new table for storing user login tokens.
     */
    public function change(): void
    {
        $this->table('web_user_login_tokens', [
            'id' => false,
            'primary_key' => ['id']
        ])->addColumn(
                (new Column())
                    ->setName('id')
                    ->setType(Column::STRING)
                    ->setLimit(16)
                    ->setNull(false)
            )->addColumn(
                (new Column())
                    ->setName('verifier')
                    ->setType(Column::STRING)
                    ->setLimit(128)
                    ->setNull(false)
            )->addColumn(
                (new Column())
                    ->setName('creator')
                    ->setType(Column::INTEGER)
                    ->setNull(false)
            )->addTimestamps(null, false)
                ->addForeignKey(
                'creator',
                'web_users',
                'id',
                [
                    'delete' => 'CASCADE',
                    'update' => 'CASCADE',
                ]
            )->create();
    }
}
