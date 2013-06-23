<?php
namespace Metagist\Worker\Scanner;

require_once __DIR__ . '/bootstrap.php';

/**
 * Tests the package scanner decorator.
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
class PackagistTest extends \PHPUnit_Framework_TestCase
{
    /**
     * system under test
     *  
     * @var Packagist
     */
    private $scanner;
    
    /**
     * app 
     * 
     * @var \Metagist\Worker\Application
     */
    private $application;
    
    /**
     * client
     *  
     * @var \Packagist\Api\Client
     */
    private $client;
    
    /**
     * Test setup
     */
    public function setUp()
    {
        parent::setUp();
        $this->application = new \Metagist\Worker\Application();
        $this->application['monolog'] = $this->getMock("Psr\Log\LoggerInterface");
        
        $this->scanner     = new Packagist($this->application);
        $this->client      = $this->getMock("\Packagist\Api\Client");
        
        $this->scanner->setPackagistClient($this->client);
    }
    
    /**
     * Ensures the information is extracted from the response.
     */
    public function testScan()
    {
        $this->client->expects($this->once())
            ->method('get')
            ->with('test/test')
            ->will($this->returnValue($this->createPackagistPackage()));
        
        $package = new \Metagist\Package('test/test');
        $infos   = $this->scanner->scan($package);
        
        $this->assertInternalType("array", $infos);
        $this->assertEquals(6, count($infos));
        $this->assertInstanceOf("\Metagist\MetaInfo", current($infos));
    }
    
    /**
     * Creates a mock.
     * 
     * @return \Packagist\Api\Result\Package
     */
    protected function createPackagistPackage()
    {
        $package = $this->getMockBuilder("\Packagist\Api\Result\Package")
            ->disableOriginalConstructor()
            ->getMock();
        
        $version = $this->getMock("\Packagist\Api\Result\Package\Version");
        $version->expects($this->once())
            ->method('getLicense')
            ->will($this->returnValue(array('test')));
        $versions = array(
            $version
        );
        $package->expects($this->once())
            ->method('getVersions')
            ->will($this->returnValue($versions));
        
        return $package;
    }
}