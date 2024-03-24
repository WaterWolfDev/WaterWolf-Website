<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class ShortUrlsSeeder extends AbstractSeed
{
    public function getDependencies(): array
    {
        return [
            UserSeeder::class
        ];
    }

    public function run(): void
    {
        $rows = [
            [
                'creator' => UserSeeder::MODERATOR_ID,
                'short_url' => 'foo',
                'long_url' => 'https://foo.example.com/'
            ],
            [
                'creator' => UserSeeder::ADMIN_ID,
                'short_url' => 'bar',
                'long_url' => '/login'
            ]
        ];

        $this->table('web_short_urls')
            ->insert($rows)
            ->saveData();

    }
}
