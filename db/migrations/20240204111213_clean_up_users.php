<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CleanUpUsers extends AbstractMigration
{
    /**
     * Clear unused columns in the users table.
     */
    public function change(): void
    {
        // Workaround to make it actually lowercase
        $this->table('web_users')
            ->renameColumn('Is_team', 'tmp_is_team')
            ->update();

        $this->table('web_users')
            ->renameColumn('tmp_is_team', 'is_team')
            ->removeColumn('update')
            ->removeColumn('is_blue')
            ->removeColumn('site')
            ->removeColumn('dj_img')
            ->removeColumn('dealer')
            ->removeColumn('permission')
            ->removeColumn('role')
            ->removeColumn('location')
            ->removeColumn('webmin')
            ->removeColumn('pid')
            ->removeColumn('suspended')
            ->removeColumn('account')
            ->removeColumn('score')
            ->removeColumn('pressure')
            ->removeColumn('steam')
            ->removeColumn('facebook')
            ->removeColumn('twitter')
            ->removeColumn('youtube')
            ->removeColumn('youtube_id')
            ->removeColumn('game_password')
            ->removeColumn('game_country')
            ->removeColumn('first_name')
            ->removeColumn('last_name')
            ->removeColumn('profile_header')
            ->removeColumn('confirmed_em')
            ->removeColumn('allowed')
            ->removeColumn('enabled')
            ->removeColumn('rank')
            ->removeColumn('uid')
            ->removeColumn('app_load')
            ->removeColumn('game_tstamp')
            ->removeColumn('fesl_token')
            ->removeColumn('posts')
            ->removeColumn('is_mod')
            ->removeColumn('is_donator')
            ->removeColumn('donation')
            ->removeColumn('credits')
            ->removeColumn('user_access')
            ->removeColumn('session')
            ->removeColumn('usergroup')
            ->removeColumn('regip')
            ->removeColumn('signature')
            ->removeColumn('profile_yt')
            ->removeColumn('valid')
            ->removeColumn('avatar')
            ->removeColumn('edit_plugins')
            ->update();
    }
}
