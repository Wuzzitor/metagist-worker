<?php
namespace Metagist\Worker;

/**
 * Api Controller.
 * 
 * Receives "scan" requests and forwards them as gearman job.
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
class ApiController implements \Metagist\Api\WorkerInterface
{
    /**
     * the application instance
     * 
     * @var \Metagist\Worker\Application 
     */
    protected $application;

    /**
     * Constructor.
     * 
     * @param \Metagist\Worker\Application  $app
     */
    public function __construct(Application $app)
    {
        $this->application = $app;
        $this->initRoutes();
    }
    
    /**
     * routes.
     * @var array 
     */
    protected $routes = array(
        'index'     => array('match' => '/', 'method' => 'index'),
        'scan'      => array('match' => '/api/scan/{author}/{name}', 'method' => 'scan'),
    );
    
    /**
     * Setup of the api routes.
     * 
     * 
     */
    protected function initRoutes()
    {
        foreach ($this->routes as $name => $data) {
            $this->application
                ->match($data['match'], array($this, $data['method']))
                ->bind($name);
        }
    }

    /**
     * Index: returns nothing useful.
     * 
     * @return string
     */
    public function index()
    {
        //just check that the server is properly configured.
        $this->application->getApi()->server();
        return $this->application->json("Metagist Worker");
    }
    
    /**
     * Returns the package content as json.
     * 
     * @param string $author
     * @param string $name
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function scan($author, $name)
    {
        $message = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
        try {
            $consumerKey = $this->application->getApi()->validateRequest($message->__toString());
        } catch (\Metagist\Api\Exception $exception) {
            $this->application->getLogger()->error($exception->getMessage());
            return $this->application->json($exception->getMessage(), 403);
        }
        
        $this->application->getLogger()->info(
            'Received scan request from ' . $consumerKey. ' to scan ' . $author . '/' . $name
        );
        
        try {
            $this->application->requestScan($author . '/' . $name);
            return $this->application->json('Queued job to scan package ' . $author . '/' . $name);
        } catch (\Metagist\Worker\Exception $exception) {
            return $this->application->json($exception->getMessage(), 500);
        } 
    }
}