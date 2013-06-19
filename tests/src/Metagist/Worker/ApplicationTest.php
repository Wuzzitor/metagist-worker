<?php
namespace Metagist\Worker;

require_once __DIR__ . '/bootstrap.php';

/**
 * Tests the metagist worker application
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * system under test
     * @var Application
     */
    private $app;
    
    /**
     * Test setup.
     */
    public function setUp()
    {
        parent::setUp();
        $this->app = new \Metagist\Worker\Application();
        $this->app['monolog'] = $this->getMock("\Psr\Log\LoggerInterface");
        
        if (!defined('GEARMAN_SUCCESS')) {
            define('GEARMAN_SUCCESS', 'GEARMAN_SUCCESS');
        }
    }
    
    /**
     * Ensures the api getter returns what is registered under \Metagist\Api\ServiceProvider::API
     */
    public function testGetApi()
    {
        $serviceProvider =  $this->getMock("\Metagist\Api\ServiceProvider");
        $this->app[\Metagist\Api\ServiceProvider::API] = $serviceProvider;
        $api = $this->app->getApi();
        $this->assertSame($serviceProvider, $api);
    }


    /**
     * Ensures the job is created on the gearman client.
     */
    public function testRequestScan()
    {
        $client = $this->getMock("\GearmanClient", array('doBackground', 'returnCode'));
        $this->app->setGearmanClient($client);
        
        $client->expects($this->once())
            ->method('doBackground')
            ->with('scan', 'test/123');
        
        $client->expects($this->once())
            ->method('returnCode')
            ->will($this->returnValue(GEARMAN_SUCCESS));
        
        $this->app->requestScan('test/123');
    }
    
    /**
     * Ensures an exception is throw if the client job execution fails
     */
    public function testRequestScanFails()
    {
        $client = $this->getMock("\GearmanClient", array('doBackground', 'returnCode'));
        $this->app->setGearmanClient($client);
        
        $client->expects($this->once())
            ->method('doBackground')
            ->with('scan', 'test/123');
        
        $client->expects($this->once())
            ->method('returnCode')
            ->will($this->returnValue(false));
        
        $this->setExpectedException("\Metagist\Worker\Exception");
        $this->app->requestScan('test/123');
    }
}