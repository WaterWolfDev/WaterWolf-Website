<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RemoveDmxLights extends AbstractMigration
{
    public function change(): void
    {
        $this->table('web_dmx_fixtures')
            ->drop()
            ->save();
    }
}
