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
     * Test setup.
     */
    public function setUp()
    {
        parent::setUp();
        
        $this->app = new \Metagist\Worker\Application();
        $this->app['monolog'] = $this->getMock("\Psr\Log\LoggerInterface");
        $this->apiMock        = $this->getMock("\Metagist\Api\ServiceProvider");
        $this->app[\Metagist\Api\ServiceProvider::API] = $this->apiMock;
        
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
}