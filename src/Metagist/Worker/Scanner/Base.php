<?php
namespace Metagist\Worker\Scanner;

/**
 * Base scanner class.
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
abstract class Base
{
    /**
     * application / service locator
     * 
     * @var \Metagist\Worker\Application 
     */
    protected $application;

    /**
     * logger instance
     * 
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    
    /**
     * Constructor.
     * 
     * @param \Metagist\Worker\Application $application
     */
    public function __construct(\Metagist\Worker\Application $application)
    {
        $this->application = $application;
        $this->logger      = $application->getLogger();
    }
}