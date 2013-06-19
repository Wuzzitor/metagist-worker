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
            $app->scan($package);
    })
;

return $console;
