<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class FixAvatars extends AbstractMigration
{
    /**
     * Fix users who have the previous default avatar img.
     */
    public function change(): void
    {
        $this->execute(
            <<<'SQL'
                UPDATE web_users
                SET user_img=null
                WHERE user_img = '000.png'
            SQL
        );
    }
}
