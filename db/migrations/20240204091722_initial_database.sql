SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `dj_albums`;
DROP TABLE IF EXISTS `dj_songs`;
DROP TABLE IF EXISTS `internal_finance_audit`;
DROP TABLE IF EXISTS `logs`;
DROP TABLE IF EXISTS `web_announce`;
DROP TABLE IF EXISTS `web_asset_purchases`;
DROP TABLE IF EXISTS `web_assets`;

CREATE TABLE IF NOT EXISTS `web_banlist` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `ip` varchar(50) DEFAULT NULL,
    `uid` int(11) DEFAULT NULL,
    `tstamp` int(11) DEFAULT NULL,
    `guid` int(11) DEFAULT NULL,
    `attempt` int(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `ip` (`ip`),
    KEY `guid` (`guid`),
    KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

DROP TABLE IF EXISTS `web_bbcode`;

DROP TABLE IF EXISTS `web_bookmarks`;

DROP TABLE IF EXISTS `web_clicks`;

CREATE TABLE IF NOT EXISTS `web_comments` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `comment_group` varchar(50) DEFAULT NULL,
    `comment` text DEFAULT NULL,
    `location` text DEFAULT NULL,
    `tstamp` int(11) DEFAULT NULL,
    `like_count` int(11) DEFAULT 0,
    `creator` int(11) DEFAULT NULL,
    KEY `id` (`id`,`creator`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `web_dmx_fixtures` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `fixture_name` varchar(255) NOT NULL,
    `universe_number` int(11) NOT NULL,
    `channel_number` int(11) NOT NULL,
    `rig_name` varchar(50) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE IF NOT EXISTS `web_events` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `creator` int(11) NOT NULL,
    `event` varchar(50) NOT NULL DEFAULT 'none',
    `url` text NOT NULL,
    `tstamp` int(11) NOT NULL,
    `slot` int(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `web_groups`;

DROP TABLE IF EXISTS `web_livechat`;

DROP TABLE IF EXISTS `web_pages`;

DROP TABLE IF EXISTS `web_pm`;

CREATE TABLE IF NOT EXISTS `web_posters` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `creator` int(11) DEFAULT NULL,
    `last_viewed` int(11) DEFAULT NULL,
    `location` varchar(50) DEFAULT NULL,
    `file` varchar(50) DEFAULT NULL,
    `views` int(11) DEFAULT 0,
    `mode` int(11) DEFAULT 0 COMMENT '0 Default, 1 Artwork',
    PRIMARY KEY (`id`),
    KEY `id` (`id`),
    KEY `mode` (`mode`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE IF NOT EXISTS `web_sessions` (
    `uid` int(11) DEFAULT NULL,
    `sessionid` varchar(32) DEFAULT NULL,
    `ip` varchar(39) DEFAULT NULL,
    `time` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

DROP TABLE IF EXISTS `web_socket`;

DROP TABLE IF EXISTS `web_status`;

CREATE TABLE IF NOT EXISTS `web_user_skills` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `creator` int(11) DEFAULT NULL,
    `skill` varchar(100) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `web_users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(24) NOT NULL DEFAULT 'DELETEME',
    `password` varchar(255) NOT NULL,
    `update` varchar(50) NOT NULL DEFAULT '0',
    `Is_team` int(11) NOT NULL DEFAULT 0,
    `is_dj` int(11) NOT NULL DEFAULT 0,
    `dj_name` varchar(50) NOT NULL DEFAULT 'Skrillix',
    `dj_genre` varchar(50) NOT NULL DEFAULT '0',
    `is_admin` int(11) NOT NULL DEFAULT 0,
    `is_blue` int(11) NOT NULL DEFAULT 0,
    `team_type` varchar(50) NOT NULL DEFAULT '0',
    `website` varchar(18) DEFAULT NULL,
    `site` varchar(50) NOT NULL DEFAULT 'engineerisaac.com',
    `wolfbuck` decimal(20,6) unsigned NOT NULL DEFAULT 0.000000,
    `aboutme` text DEFAULT NULL,
    `user_img` varchar(50) DEFAULT '000.png',
    `dj_img` varchar(50) DEFAULT NULL,
    `email` varchar(50) DEFAULT NULL,
    `dealer` int(10) unsigned NOT NULL DEFAULT 0,
    `permission` int(10) unsigned NOT NULL DEFAULT 10,
    `role` varchar(24) DEFAULT 'user',
    `ref` varchar(24) DEFAULT 'user',
    `location` int(10) unsigned NOT NULL DEFAULT 0,
    `webmin` int(11) NOT NULL DEFAULT 0,
    `active` int(11) DEFAULT 1,
    `pid` int(11) NOT NULL DEFAULT 0,
    `suspended` int(11) NOT NULL DEFAULT 0,
    `account` int(10) unsigned DEFAULT 0,
    `score` int(10) unsigned DEFAULT 0,
    `pressure` int(10) unsigned NOT NULL DEFAULT 0,
    `username_16` varchar(24) NOT NULL DEFAULT 'DELETEME',
    `bf2_key` varchar(24) DEFAULT NULL,
    `token` varchar(50) DEFAULT NULL,
    `twitch` varchar(24) DEFAULT NULL,
    `steam` varchar(24) DEFAULT NULL,
    `vrchat` varchar(24) DEFAULT NULL,
    `discord` varchar(24) DEFAULT NULL,
    `vrcdn` tinytext DEFAULT NULL,
    `vrcdn_show` int(11) DEFAULT 0,
    `facebook` varchar(24) DEFAULT NULL,
    `twitter` varchar(24) DEFAULT NULL,
    `youtube` varchar(24) DEFAULT NULL,
    `youtube_id` varchar(50) DEFAULT NULL,
    `game_password` varchar(50) DEFAULT NULL,
    `game_country` varchar(50) DEFAULT NULL,
    `first_name` varchar(50) DEFAULT NULL,
    `last_name` varchar(50) DEFAULT NULL,
    `profile_header` varchar(50) NOT NULL DEFAULT 'placeholder.png',
    `confirmed_em` tinyint(3) unsigned NOT NULL DEFAULT 0,
    `badpass` int(11) DEFAULT 0,
    `goodpass` int(11) DEFAULT 0,
    `allowed` int(11) DEFAULT 0,
    `enabled` int(11) DEFAULT 0,
    `rank` int(11) DEFAULT 0,
    `uid` int(11) DEFAULT 0,
    `app_load` int(11) DEFAULT 0,
    `game_tstamp` int(11) DEFAULT 0,
    `fesl_token` int(11) DEFAULT 0,
    `posts` int(10) unsigned NOT NULL DEFAULT 0,
    `is_mod` int(11) DEFAULT 0,
    `is_donator` int(11) DEFAULT 0,
    `donation` int(11) NOT NULL DEFAULT 0,
    `lastip` varchar(39) DEFAULT NULL,
    `credits` varchar(18) DEFAULT NULL,
    `user_access` varchar(18) DEFAULT NULL,
    `session` int(10) unsigned DEFAULT 0,
    `usergroup` int(10) unsigned NOT NULL DEFAULT 0,
    `regip` varchar(50) NOT NULL DEFAULT '0',
    `signature` mediumtext DEFAULT NULL,
    `country` varchar(4) NOT NULL DEFAULT 'US',
    `reg_date` int(11) NOT NULL,
    `lastactive` int(11) DEFAULT NULL,
    `last_login` int(11) DEFAULT NULL,
    `profile_yt` varchar(15) DEFAULT 'mEqcala-NiE',
    `online` int(11) DEFAULT 0,
    `banned` tinyint(1) NOT NULL DEFAULT 0,
    `valid` tinyint(1) NOT NULL DEFAULT 0,
    `avatar` tinytext DEFAULT NULL,
    `edit_plugins` int(11) DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `username_3` (`username`),
    KEY `username` (`username`),
    KEY `last_active` (`lastactive`),
    KEY `username_password` (`username`,`password`),
    KEY `username_email` (`username`,`email`),
    KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

DROP TABLE IF EXISTS `web_vars`;

DROP TABLE IF EXISTS `web_video_cata`;

DROP TABLE IF EXISTS `web_video_views`;

DROP TABLE IF EXISTS `web_videos`;

CREATE TABLE IF NOT EXISTS `web_world_views` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `ip` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
    `world` int(11) DEFAULT NULL,
    `creator` int(11) DEFAULT NULL,
    `tstamp` int(11) DEFAULT NULL,
    `hits` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE IF NOT EXISTS `web_worlds` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `creator` int(11) DEFAULT 1,
    `title` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT '0',
    `image` varchar(100) DEFAULT NULL,
    `youtubeid` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT '0',
    `world_id` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT '0',
    `world_creator` varchar(50) DEFAULT '0',
    `platform` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT 'PC',
    `yt_creator` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
    `vgroup` int(11) DEFAULT 1,
    `featured` int(11) DEFAULT 0,
    `owner` int(11) DEFAULT 0,
    `description` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
    `show` int(11) DEFAULT 1,
    `hits` int(11) DEFAULT 0,
    `type` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
    `game` int(11) NOT NULL DEFAULT 0,
    `tstamp` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
    KEY `id` (`id`),
    KEY `title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

SET FOREIGN_KEY_CHECKS=1;
