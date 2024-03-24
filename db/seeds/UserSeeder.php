<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class UserSeeder extends AbstractSeed
{
    public const USER_ID = 1;
    public const TEAM_MEMBER_ID = 2;
    public const MODERATOR_ID = 3;
    public const ADMIN_ID = 4;
    public const BANNED_ID = 5;

    /**
     * Create sample users.
     */
    public function run(): void
    {
        $password = password_hash(
            'WaterWolf!',
            PASSWORD_ARGON2ID
        );

        $rows = [
            [
                'id' => self::USER_ID,
                'username' => 'User',
                'email' => 'user@waterwolf.dev',
                'password' => $password,
                'reg_date' => time(),
            ],
            [
                'id' => self::TEAM_MEMBER_ID,
                'username' => 'TeamMember',
                'email' => 'teammember@waterwolf.dev',
                'password' => $password,
                'reg_date' => time(),
                'is_team' => 1,
            ],
            [
                'id' => self::MODERATOR_ID,
                'username' => 'Moderator',
                'email' => 'mod@waterwolf.dev',
                'password' => $password,
                'reg_date' => time(),
                'is_mod' => 1,
            ],
            [
                'id' => self::ADMIN_ID,
                'username' => 'Admin',
                'email' => 'admin@waterwolf.dev',
                'password' => $password,
                'reg_date' => time(),
                'is_admin' => 1,
            ],
            [
                'id' => self::BANNED_ID,
                'username' => 'Banned',
                'email' => 'banned@waterwolf.dev',
                'password' => $password,
                'reg_date' => time(),
                'banned' => 1,
            ]
        ];

        $this->table('web_users')
            ->insert($rows)
            ->saveData();
    }
}
