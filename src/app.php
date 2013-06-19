<?php
/**
 * Metagist Worker application bootstrapping
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */

require_once __DIR__ . '/../config/config.php';

/*
 * Monolog
 */
use Silex\Provider\MonologServiceProvider;
$app->register(new MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__ . '/../log/app.log',
    'monolog.name' => 'app',
    'monolog.level' => 300 // = Logger::WARNING
));

/**
 * Enable the metagist api
 */
$app->register(new Metagist\Api\ServiceProvider());

return $app;