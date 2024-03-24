<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddForeignKeys extends AbstractMigration
{
    /**
     * Update user table and set up foreign keys for UIDs.
     */
    public function change(): void
    {
        $this->addForeignKey('web_comments');
        $this->addForeignKey('web_posters');
        $this->addForeignKey('web_sessions', 'uid');
        $this->addForeignKey('web_user_skills');
    }

    private function addForeignKey(string $table, string $column = 'creator'): void
    {
        if ($this->isMigratingUp()) {
            $this->execute(
                <<<SQL
                    DELETE FROM {$table}
                    WHERE {$column} NOT IN (
                        SELECT id FROM web_users
                    )
                SQL
            );
        }

        $this->table($table)
            ->addForeignKey(
                $column,
                'web_users',
                'id',
                [
                    'delete' => 'CASCADE',
                    'update' => 'CASCADE',
                ]
            )->update();
    }
}
