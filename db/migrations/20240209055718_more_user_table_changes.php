<?php

declare(strict_types=1);

use Phinx\Db\Table\Column;
use Phinx\Migration\AbstractMigration;

final class MoreUserTableChanges extends AbstractMigration
{
    /**
     * Another round of user table changes.
     */
    public function change(): void
    {
        $this->table('web_users')
            ->changeColumn(
                'username',
                (new Column())
                    ->setType(Column::STRING)
                    ->setLimit(24)
                    ->setNull(false)
                    ->setDefault(null)
            )->changeColumn(
                'email',
                (new Column())
                    ->setType(Column::STRING)
                    ->setLimit(100)
                    ->setNull(false)
                    ->setAfter('username')
            )->changeColumn(
                'is_team',
                (new Column())
                    ->setType(Column::BOOLEAN)
                    ->setNull(false)
                    ->setDefault(0)
            )->changeColumn(
                'is_admin',
                (new Column())
                    ->setType(Column::BOOLEAN)
                    ->setAfter('is_team')
                    ->setNull(false)
                    ->setDefault(0)
            )->changeColumn(
                'is_dj',
                (new Column())
                    ->setType(Column::BOOLEAN)
                    ->setNull(false)
                    ->setDefault(0)
            )->changeColumn(
                'banned',
                (new Column())
                    ->setType(Column::BOOLEAN)
                    ->setAfter('password')
                    ->setNull(false)
                    ->setDefault(0)
            )->changeColumn(
                'dj_name',
                (new Column())
                    ->setType(Column::STRING)
                    ->setLimit(150)
                    ->setNull(true)
                    ->setDefault(null)
            )->changeColumn(
                'dj_genre',
                (new Column())
                    ->setType(Column::STRING)
                    ->setLimit(50)
                    ->setNull(true)
                    ->setDefault(null)
            )->changeColumn(
                'team_type',
                (new Column())
                    ->setType(Column::STRING)
                    ->setLimit(50)
                    ->setNull(true)
                    ->setDefault(null)
            )->addColumn(
                (new Column())
                    ->setName('pronouns')
                    ->setType(Column::STRING)
                    ->setLimit(50)
                    ->setNull(true)
                    ->setAfter('title')
            )->changeColumn(
                'user_img',
                (new Column())
                    ->setType(Column::STRING)
                    ->setLimit(50)
                    ->setNull(true)
                    ->setDefault(null)
            )->changeColumn(
                'ref',
                (new Column())
                    ->setType(Column::STRING)
                    ->setLimit(24)
                    ->setNull(true)
                    ->setDefault(null)
            )->changeColumn(
                'vrcdn_show',
                (new Column())
                    ->setType(Column::BOOLEAN)
                    ->setNull(false)
                    ->setDefault(0)
            )->changeColumn(
                'country',
                (new Column())
                    ->setType(Column::STRING)
                    ->setLimit(4)
                    ->setNull(true)
                    ->setDefault(null)
            )->changeColumn(
                'online',
                (new Column())
                    ->setType(Column::BOOLEAN)
                    ->setNull(false)
                    ->setDefault(0)
            )->removeColumn('wolfbuck')
            ->removeColumn('username_16')
            ->removeColumn('bf2_key')
            ->removeColumn('token')
            ->update();

        if ($this->isMigratingUp()) {
            $this->execute(
                <<<'SQL'
                    UPDATE web_users
                    SET team_type=null
                    WHERE team_type = '0'
                SQL
            );

            $this->execute(
                <<<'SQL'
                    UPDATE web_users
                    SET dj_name=null
                    WHERE dj_name = 'Skrillix'
                SQL
            );

            $this->execute(
                <<<'SQL'
                    UPDATE web_users
                    SET dj_genre=null
                    WHERE dj_genre = '0'
                SQL
            );
        }
    }
}
