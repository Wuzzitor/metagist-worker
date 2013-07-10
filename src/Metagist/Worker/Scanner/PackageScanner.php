<?php
namespace Metagist\Worker\Scanner;

use Metagist\Worker\Application;

/**
 * Scanner decorator.
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
class PackageScanner extends Base implements ScannerInterface
{
    /**
     * config key
     * 
     * @var string
     */
    const ENABLED_SCANNERS = 'metagist.worker.scanners';
    
    /**
     * server interface
     * 
     * @var \Metagist\Api\ServerInterface 
     */
    private $server;
    
    /**
     * Constructor
     * 
     * @param \Metagist\Worker\Application 
     */
    public function __construct(Application $application)
    {
        parent::__construct($application);
        $this->server      = $application->getApi()->server();
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
            if (!is_subclass_of($class, "\Metagist\Worker\Scanner\ScannerInterface") ) {
                $this->logger->warning('Ignoring ' . $class . ', does not implement ScannerInterface.');
                return null;
            }
            
            $this->logger->info('Using scanner ' . $class);
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
     * @return \Metagist\MetaInfo[]
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
     * @return boolean
     */
    public function executeScanJob(\GearmanJob $job)
    {
        $workload = $job->workload();
        $this->logger->info("Received job: " . $job->handle() . " to scan package " . $workload);
        
        $metaInfos =  $this->scanByPackageIdentifier($workload);
        list ($author, $name) = explode('/', $workload);
        foreach ($metaInfos as $metaInfo) {
            $this->pushInfo($metaInfo, $author, $name);
        }

        return true;
    }
    
    /**
     * Attemps to push a metainfo to the server.
     * 
     * Exceptions are caught and logged.
     * 
     * @param \Metagist\MetaInfo $metaInfo
     * @param string             $author
     * @param string             $name
     */
    public function pushInfo(\Metagist\MetaInfo $metaInfo, $author, $name)
    {
        try {
            $this->server->pushInfo($author, $name, $metaInfo);
        } catch (\Exception $exception) {
            $this->logger->error(
                'Error trying to push ' . $metaInfo->getGroup() . ': ' 
                . $exception->getMessage()
            );
        }
    }
}