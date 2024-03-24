<?php

require(dirname(__DIR__) . '/vendor/autoload.php');

$app = App\AppFactory::createApp();
$app->run();
