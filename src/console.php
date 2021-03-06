<?php
/**
 * Metagist console app setup.
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

use Monolog\Logger;

/* @var $app \Metagist\Worker\Application */

$console = new Application('Metagist Worker', '0.1');

/**
 * Register the "scan" command.
 */
$console
    ->register('scan')
    ->setName('scan')
    ->setDescription('Scan a package for metainfo')
    ->addArgument('package', InputArgument::REQUIRED, 'The identifier (author/name) of the package')
    ->setHelp(<<<EOT
The <info>scan</info> retrieves information on the given using different sources.
EOT
        )
        ->setCode(function (InputInterface $input, OutputInterface $output) use ($app) {
            $identifier = $input->getArgument('package');
            $log = $app['monolog']; /* @var $log \Monolog\Logger */
            $log->pushHandler(new \Monolog\Handler\StreamHandler('php://stdout', Logger::INFO));
            $app->scanPackage($identifier);
    })
;
    
/**
 * Register stub command "expect-request"
 */
$console
    ->register('expect-request')
    ->setName('expect-request')
    ->setDescription('Waits for scan jobs ')
    ->setHelp('The <info>expect-request</info> command launches a gearman worker.')
    ->setCode(function (InputInterface $input, OutputInterface $output) use ($app) {
        $app->scan();
    })
;

/**
 * Register a command to follow the packagist feed
 */
$console
    ->register('follow-packagist')
    ->setName('follow-packagist')
    ->setDescription('Scans the packagist.org package updated feed.')
    ->setHelp('The <info>follow-packagist</info> scans the packagist.org feed (use as cronjob).')
    ->setCode(function (InputInterface $input, OutputInterface $output) use ($app) {
        $app->followPackagist();
    })
;
    
/**
 * Consume packagist.org feeds
 */

return $console;