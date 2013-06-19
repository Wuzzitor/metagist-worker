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
     * gearman client instance.
     * 
     * @var \GearmanClient 
     */
    protected $gearmanClient;
    
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
        $gmClient->doBackground("scan", $package);

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
     * @param string $package identifier
     */
    public function scan($package)
    {
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
}