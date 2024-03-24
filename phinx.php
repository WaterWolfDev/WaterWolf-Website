<?php
/*
 * This file configures Phinx, the database migrations tool.
 */

require (__DIR__) . '/vendor/autoload.php';

$env = new App\Environment($_ENV);
$dbInfo = $env->getDatabaseInfo();

$di = App\AppFactory::buildContainer($env);
$db = $di->get(Doctrine\DBAL\Connection::class);

return
    [
        'paths' => [
            'migrations' => '%%PHINX_CONFIG_DIR%%/db/migrations',
            'seeds' => '%%PHINX_CONFIG_DIR%%/db/seeds',
        ],
        'environments' => [
            'default_migration_table' => 'migrations',
            'default_environment' => 'db',
            'db' => [
                'name' => $dbInfo['dbname'],
                'connection' => $db->getNativeConnection(),
            ],
        ],
        'version_order' => 'creation',
    ];
