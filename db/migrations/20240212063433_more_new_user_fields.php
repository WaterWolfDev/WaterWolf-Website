<?php

declare(strict_types=1);

use Phinx\Db\Table\Column;
use Phinx\Migration\AbstractMigration;

final class MoreNewUserFields extends AbstractMigration
{
    /**
     * Add more user data.
     */
    public function change(): void
    {
        $this->table('web_users')
            ->addColumn(
                (new Column())
                    ->setName('dj_img')
                    ->setType(Column::STRING)
                    ->setLimit(50)
                    ->setNull(true)
                    ->setAfter('dj_genre')
            )->addColumn(
                (new Column())
                    ->setName('atproto_did')
                    ->setType(Column::STRING)
                    ->setLimit(128)
                    ->setNull(true)
                    ->setAfter('vrcdn_show')
            )->addColumn(
                (new Column())
                    ->setName('is_mod')
                    ->setType(Column::BOOLEAN)
                    ->setNull(false)
                    ->setDefault(0)
                    ->setAfter('is_admin')
            )->update();
    }
}
