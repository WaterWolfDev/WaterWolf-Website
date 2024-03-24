<?php

declare(strict_types=1);

use Phinx\Db\Table\Column;
use Phinx\Migration\AbstractMigration;

final class ModifyUserTables extends AbstractMigration
{
    /**
     * Modify user table values.
     */
    public function change(): void
    {
        $this->table('web_users')
            ->changeColumn(
                'website',
                (new Column())
                    ->setType(Column::STRING)
                    ->setLimit(255)
                    ->setNull(true)
            )->addColumn(
                (new Column())
                    ->setName('title')
                    ->setType(Column::STRING)
                    ->setLimit(255)
                    ->setNull(true)
                    ->setAfter('aboutme')
            )->update();

        if ($this->isMigratingUp()) {
            $this->execute(
                <<<'SQL'
                    UPDATE web_users
                    SET title=aboutme
                    WHERE is_team = 1
                SQL
            );
        }
    }
}
