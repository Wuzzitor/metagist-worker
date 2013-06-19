<?php
/**
 * Metagist worker configuration.
 * 
 * 
 */

ini_set('display_errors', 1);
error_reporting(-1);
$app['debug'] = true;

/*
 * api config
 */
$app[\Metagist\Api\ServiceProvider::APP_SERVER_CONFIG] = array(
    'base_url' => 'http://metagist.dev/api/',
    'consumer_key' => 'dev-test',
    'consumer_secret' => 'dev-test',
);
$app[\Metagist\Api\ServiceProvider::APP_SERVICES] = __DIR__ . '/../config/services.json';