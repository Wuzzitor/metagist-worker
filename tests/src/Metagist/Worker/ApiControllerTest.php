<?php
namespace Metagist\Worker;

require_once __DIR__ . '/bootstrap.php';

/**
 * Tests the metagist worker api controller
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
class ApiControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * app 
     * @var Application
     */
    private $app;
    
    /**
     * app 
     * @var Application
     */
    private $apiMock;
    
    /**
     * system under test
     * @var ApiController
     */
    private $controller;
    
    /**
     * gearman client mock
     * 
     * @var \Gearman
     */
    private $gearman;
    
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
        
        /*
         * gearman mocking
         */
        if (!defined('GEARMAN_SUCCESS')) {
            define('GEARMAN_SUCCESS', 'GEARMAN_SUCCESS');
        }
        $this->gearman = $this->getMock("\GearmanClient", array('doBackground', 'returnCode'));
        $this->app->setGearmanClient($this->gearman);
        
        $this->controller = new ApiController($this->app);
    }
    
    /**
     * Ensures the scan request is queued.
     */
    public function testScan()
    {
        $this->gearman->expects($this->once())
            ->method('returnCode')
            ->will($this->returnValue(GEARMAN_SUCCESS));
        
        $response = $this->controller->scan('test', 'test');
        $this->assertInstanceOf("\Symfony\Component\HttpFoundation\JsonResponse", $response);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('Queued job to scan package', $response->getContent());
    }
    
    /**
     * Ensures the request is validated using oauth
     */
    public function testRequestValidationFails()
    {
        $this->gearman->expects($this->never())
            ->method('doBackground');
        $this->apiMock->expects($this->once())
            ->method('validateRequest')
            ->will($this->throwException(new \Metagist\Api\Exception('test')));
        $this->app['monolog']->expects($this->once())
            ->method('error');
        
        $response = $this->controller->scan('test', 'test');
        $this->assertEquals(403, $response->getStatusCode());
    }
    
    /**
     * 
     */
    public function testGearmanException()
    {
        $this->gearman->expects($this->once())
            ->method('returnCode')
            ->will($this->returnValue(FALSE));
        
        $response = $this->controller->scan('test', 'test');
        $this->assertEquals(500, $response->getStatusCode());
    }
}