<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class PosterTypesSeeder extends AbstractSeed
{
    /**
     * Add poster types
     */
    public function run(): void
    {
        $rows = [
            [
                'id' => 'advert',
                'description' => 'Advertisements'
            ],
            [
                'id' => 'dj',
                'description' => 'DJ Poster'
            ],
            [
                'id' => 'event',
                'description' => 'Events',
            ],
            [
                'id' => 'meme',
                'description' => 'Memes',
            ],
            [
                'id' => 'photography',
                'description' => 'Photography'
            ]
        ];

        $this->table('web_poster_types')
            ->insert($rows)
            ->saveData();
    }
}
