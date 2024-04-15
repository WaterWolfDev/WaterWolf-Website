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
        $redirects = [
            'discord' => 'https://discord.gg/waterwolf',
            'github' => 'https://github.com/WaterWolfDev',
            'twitch' => 'https://www.twitch.tv/waterwolfvr',
            'twitter' => 'https://twitter.com/waterwolftown',
            'x' => 'https://twitter.com/waterwolftown',
            'vrchat' => 'https://vrc.group/WWOLF.1912',
        ];

        $rows = [];
        foreach ($redirects as $shortUrl => $longUrl) {
            $rows[] = [
                'creator' => UserSeeder::ADMIN_ID,
                'short_url' => $shortUrl,
                'long_url' => $longUrl,
            ];
        }

        $this->table('web_short_urls')
            ->insert($rows)
            ->saveData();

    }
}
