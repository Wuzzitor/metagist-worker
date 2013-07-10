<?php
namespace Metagist\Worker\Scanner;

require_once __DIR__ . '/bootstrap.php';

/**
 * Tests the package scanner decorator.
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
class PackageScannerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * system under test
     *  
     * @var PackageScanner
     */
    private $scanner;
    
    /**
     * app 
     * @var Application
     */
    private $apiMock;
    
    /**
     * server client mock
     * 
     * @var \Metagist\Api\ServerInterface
     */
    private $serverMock;
    
    /**
     * Test setup.
     */
    public function setUp()
    {
        parent::setUp();
        
        $this->app = new \Metagist\Worker\Application();
        $this->app['monolog'] = $this->getMock("\Psr\Log\LoggerInterface");
        $this->apiMock        = $this->getMock("\Metagist\Api\ServiceProvider");
        $apiMock = $this->apiMock;
        $this->app[\Metagist\Api\ServiceProvider::API] = $this->app->share(
            function () use ($apiMock) {return $apiMock;}
        );
        $this->serverMock = $this->getMock("\Metagist\Api\ServerInterface");
        $this->apiMock->expects($this->any())
            ->method('server')
            ->will($this->returnValue($this->serverMock));
        
        $this->app[PackageScanner::ENABLED_SCANNERS] = array();
         
        $this->scanner = new PackageScanner($this->app);
    }
    
    /**
     * Ensures that a configured scanner instance is called.
     */
    public function testScan()
    {
        $scannerMock = $this->getMock("\Metagist\Worker\Scanner\ScannerInterface");
        $this->app[PackageScanner::ENABLED_SCANNERS] = array(
            $scannerMock
        );
        $package = new \Metagist\Package('test/test');
        $scannerMock->expects($this->once())
            ->method('scan')
            ->with($package);
        $this->scanner->scan($package);
    }
    
    /**
     * Ensures that a configured scanner name is instantiated and is called.
     */
    public function testScanWithScannerClassName()
    {
        $scannerMock = $this->getMock("\Metagist\Worker\Scanner\ScannerInterface");
        $className   = get_class($scannerMock);
        $this->app[PackageScanner::ENABLED_SCANNERS] = array(
            $className
        );
        
        $package = new \Metagist\Package('test/test');
        $this->scanner->scan($package);
    }
    
    /**
     * Ensures a gearman job triggers scanning.
     */
    public function testExecuteScanJob()
    {
        $job = $this->getMock("\GearmanJob", array("workload", 'handle'));
        $job->expects($this->once())
            ->method('workload')
            ->will($this->returnValue("test/test"));
        $job->expects($this->once())
            ->method('handle')
            ->will($this->returnValue("some job name"));
        
        $package = new \Metagist\Package('test/test');
        $this->serverMock->expects($this->once())
            ->method('package')
            ->will($this->returnValue($package));
        
        $this->scanner->executeScanJob($job);
    }
    
    /**
     * Tests the push method.
     */
    public function testPushInfo()
    {
        $info = \Metagist\MetaInfo::fromValue('test', 'test');
        $this->serverMock->expects($this->once())
            ->method('pushInfo')
            ->will($this->returnValue(true));
        
        $this->scanner->pushInfo($info, 'test', 'test');
    }
    
    /**
     * Ensures that exceptions are caught.
     */
    public function testPushInfoCatchesException()
    {
        $info = \Metagist\MetaInfo::fromValue('test', 'test');
        
        $this->serverMock->expects($this->once())
            ->method('pushInfo')
            ->will($this->throwException(new \Exception('test')));
        
        $this->setExpectedException(NULL);
        $this->scanner->pushInfo($info, 'test', 'test');
    }
}