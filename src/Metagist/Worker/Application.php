<?php

/**
 * Application.php
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */

namespace Metagist\Worker;

use Silex\Application as SilexApp;

/**
 * Worker application.
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
class Application extends SilexApp
{
    /**
     * name of the scan task
     * 
     * @var string
     */
    const GEARMAN_SCAN_TASK = 'scan';
    
    /**
     * config key for the feed consumer
     * 
     * array keys:
     * cache_dir
     * 
     * @var string
     */
    const FEED_CONFIG = 'metagist.worker.feed';

    /**
     * gearman client instance.
     * 
     * @var \GearmanClient 
     */
    protected $gearmanClient;
    
    /**
     * gearman worker instance.
     * 
     * @var \GearmanWorker
     */
    protected $gearmanWorker;

    /**
     * Returns the Api service provider.
     * 
     * @return \Metagist\Api\ServiceProvider
     */
    public function getApi()
    {
        return $this[\Metagist\Api\ServiceProvider::API];
    }

    /**
     * Returns the monolog logger.
     * 
     * @return \Psr\Log\LoggerInterface;
     */
    public function getLogger()
    {
        return $this['monolog'];
    }

    /**
     * Launches a new job (called by api controller).
     * 
     * @throws \Metagist\Worker\Exception
     */
    public function requestScan($package)
    {
        $gmClient = $this->getGearmanClient();
        $gmClient->doBackground(self::GEARMAN_SCAN_TASK, $package);

        $code = $gmClient->returnCode();
        if ($code != GEARMAN_SUCCESS) {
            $message = 'Scan job initiation failed with code ' . $code;
            $this->getLogger()->error($message);
            throw new Exception($message, $code);
        }
    }

    /**
     * Does the real job.
     * 
     */
    public function scan()
    {
        $gmworker = $this->getGearmanWorker();
        
        while ($gmworker->work()) {
            $code = $gmworker->returnCode();
            if ($code != GEARMAN_SUCCESS) {
                throw new Exception('Gearman worker error: ' . $code, $code);
                break;
            }
        }
    }

    /**
     * Manual scan initialisation.
     * 
     * @param string $identifier
     * @return \Metagist\MetaInfo[]
     */
    public function scanPackage($identifier)
    {
        $scanner = new \Metagist\Worker\Scanner\PackageScanner($this);
        return $scanner->scanByPackageIdentifier($identifier);
    }
    
    /**
     * Follows the packagist.org feed, scans every updated package.
     * 
     */
    public function followPackagist()
    {
        $this->enableConsoleLogOutput();
        $reader = new FeedReader($this->createFeed(), $this->createPackageScanner());
        $reader->read();
    }
    
    /**
     * Enable console output.
     */
    protected function enableConsoleLogOutput()
    {
        $this->getLogger()->pushHandler(
            new \Monolog\Handler\StreamHandler('php://stdout', \Monolog\Logger::INFO)
        );
    }
    
    /**
     * Creates a feed reader.
     * 
     * @return \Zend\Feed\Reader\Feed\FeedInterface
     * @link http://framework.zend.com/manual/2.0/en/modules/zend.cache.storage.adapter.html#the-filesystem-adapter
     */
    protected function createFeed()
    {
        $config  = $this[self::FEED_CONFIG];
        $feedUrl = $config['feed_url'];
        unset($config['feed_url']);
        
        $cache = \Zend\Cache\StorageFactory::adapterFactory('Filesystem', $config);
        \Zend\Feed\Reader\Reader::setCache($cache);
        \Zend\Feed\Reader\Reader::useHttpConditionalGet();
        $adapter = new \Zend\Http\Client\Adapter\Curl();
        $client = \Zend\Feed\Reader\Reader::getHttpClient();
        $client->setAdapter($adapter);
        
        $cert = realpath(__DIR__ . '/../../config/packagist.org.pem');
        $adapter->setOptions(array(
            'curloptions' => array(
                CURLOPT_CAPATH => dirname($cert),
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_CAINFO => $cert,
            )
        ));

        return \Zend\Feed\Reader\Reader::import($feedUrl);
    }

    /**
     * Returns a gearman client instance. 
     * 
     * Creates one on the fly if no one injected.
     * 
     * @return \GearmanClient
     */
    protected function getGearmanClient()
    {
        if ($this->gearmanClient === null) {
            $gmclient = new \GearmanClient();
            $gmclient->addServer();
            return $gmclient;
        }

        return $this->gearmanClient;
    }

    /**
     * Inject a gearman client.
     * 
     * @param \GearmanClient $client
     */
    public function setGearmanClient(\GearmanClient $client)
    {
        $this->gearmanClient = $client;
    }

    /**
     * Returns the gearman worker.
     * 
     * @return \GearmanWorker
     */
    protected function getGearmanWorker()
    {
        if ($this->gearmanWorker === null) {
            $gmworker = new \GearmanWorker();
            $gmworker->addServer();
            $gmworker->addFunction(
                self::GEARMAN_SCAN_TASK,
                array($this->createPackageScanner(), 'executeScanJob')
            );
                
            return $gmworker;
        }
        
        return $this->gearmanWorker;
    }
    
    /**
     * Creates a package scanner instance.
     * 
     * @return \Metagist\Worker\Scanner\PackageScanner
     */
    protected function createPackageScanner()
    {
        return new \Metagist\Worker\Scanner\PackageScanner($this);
    }

    /**
     * Inject a gearman worker.
     * 
     * @param \GearmanWorker $worker
     */
    public function setGearmanWorker(\GearmanWorker $worker)
    {
        $this->gearmanWorker = $worker;
    }
}