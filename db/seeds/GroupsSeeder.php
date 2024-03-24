<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class GroupsSeeder extends AbstractSeed
{
    /**
     * Add groups for users.
     */
    public function run(): void
    {
        $rows = [
            [
                'id' => 'eufuria',
                'name' => 'EUFuria',
            ],
            [
                'id' => 'iwait',
                'name' => 'i.W.a.I.T.',
            ],
            [
                'id' => 'virtualfurmix',
                'name' => 'Virtual FurMix'
            ],
            [
                'id' => 'waterwolf',
                'name' => 'WaterWolf'
            ]
        ];

        $this->table('web_groups')
            ->insert($rows)
            ->saveData();
    }
}
