<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class UserHasGroupSeeder extends AbstractSeed
{
    public function getDependencies(): array
    {
        return [
            GroupsSeeder::class,
            UserSeeder::class
        ];
    }

    public function run(): void
    {
        $rows = [
            [
                'user_id' => UserSeeder::USER_ID,
                'group_id' => 'waterwolf'
            ]
        ];

        $this->table('web_user_has_group')
            ->insert($rows)
            ->saveData();
    }
}
