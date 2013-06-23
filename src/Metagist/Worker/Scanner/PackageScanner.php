<?php
namespace Metagist\Worker\Scanner;

use Metagist\Worker\Application;

/**
 * Scanner decorator.
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
class PackageScanner implements ScannerInterface
{
    /**
     * config key
     * 
     * @var string
     */
    const ENABLED_SCANNERS = 'metagist.worker.scanners';
    
    /**
     * application
     * 
     * @var \Metagist\Worker\Application
     */
    private $application;
    
    /**
     * server interface
     * 
     * @var \Metagist\Api\ServerInterface 
     */
    private $server;
    
    /**
     * logger instance
     * 
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    
    /**
     * Constructor
     * 
     * @param \Metagist\Worker\Application 
     */
    public function __construct(Application $application)
    {
        $this->application = $application;
        $this->server      = $application->getApi()->server();
        $this->logger      = $application->getLogger();
    }

    /**
     * Returns a collection of retrieved metainfos.
     * 
     * @param \Metagist\Package $package
     * @return \Metagist\MetaInfo[]
     */
    public function scan(\Metagist\Package $package)
    {
        $scanners  = $this->application[self::ENABLED_SCANNERS];
        $metaInfos = array(); 
        
        foreach ($scanners as $className) {
            if (! ($scanner = $this->getScannerInstance($className))) {
                continue;
            }
            $retrievedInfos = $scanner->scan($package);
            if (is_array($retrievedInfos)) {
                $metaInfos = array_merge($metaInfos, $retrievedInfos);
            }
        }
        
        $this->logger->info('Retrieved ' . count($metaInfos) . ' infos.');
        return $metaInfos;
    }
    
    /**
     * 
     * @param string|ScannerInterface $class
     * @return ScannerInterface
     */
    protected function getScannerInstance($class)
    {
        if (is_string($class)) {
            if (!is_subclass_of($class, 'ScannerInterface') ) {
                $this->logger->warning('Ignoring ' . $class);
                return null;
            }
            
            $this->logger->info('Using scanner ' . $className);
            return new $class($this->application);
        }
        
        if ($class instanceof ScannerInterface) {
            return $class;
        }
        
        $this->logger->warning('Ignoring ' . $class);
        return null;
    }
    
    /**
     * Executes a scan, retrieves remote package info.
     * 
     * @param string $identifier
     * @return type
     */
    public function scanByPackageIdentifier($identifier)
    {
        list ($author, $name) = explode('/', $identifier);
        $package = $this->server->package($author, $name);
        return $this->scan($package);
    }
    
    /**
     * Executes the gearman job.
     * 
     * @param \GearmanJob $job
     * @return type
     */
    public function executeScanJob(\GearmanJob $job)
    {
        $workload = $job->workload();
        $this->logger->info("Received job: " . $job->handle() . " to scan package " . $workload);
        
        $metaInfos =  $this->scanByPackageIdentifier($workload);
        foreach ($metaInfos as $metaInfo) {
            $this->server->pushInfo($author, $name, $metaInfo);
        }

        return true;
    }
}