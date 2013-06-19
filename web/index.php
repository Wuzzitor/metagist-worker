<?php
/**
 * Metagist Worker web access script.
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
require_once __DIR__ . '/../vendor/autoload.php';

$app = new \Metagist\Worker\Application();
require __DIR__ . '/../src/app.php';

/*
 * The api controller registers itself
 */
new Metagist\Worker\ApiController($app);

$app->run();
