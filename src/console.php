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
            $package = $input->getArgument('package');
            $app->requestScan($package);
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

return $console;
