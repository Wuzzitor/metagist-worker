<?php
namespace Metagist\Worker;

require_once __DIR__ . '/bootstrap.php';

/**
 * Tests the metagist worker application
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
class FeedReaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * system under test
     * 
     * @var \Metagist\Worker\FeedReader
     */
    private $reader;
    
    /**
     * feed mock
     * 
     * @var \Zend\Feed\Reader\Feed\FeedInterface
     */
    private $feed;
    
    /**
     * system under test
     * 
     * @var Application
     */
    private $scanner;
    
    /**
     * Test setup.
     */
    public function setUp()
    {
        parent::setUp();
        $this->feed    = $this->getMock("\Zend\Feed\Reader\Feed\FeedInterface");
        $this->scanner = $this->getMockBuilder("\Metagist\Worker\Scanner\PackageScanner")
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->reader = new FeedReader($this->feed, $this->scanner);
    }
    
    /**
     * Tests run without errors.
     */
    public function testRead()
    {
        $entry = $this->getMock("\Zend\Feed\Reader\Entry\EntryInterface");
        $entry->expects($this->once())
            ->method('getTitle')
            ->will($this->returnValue('author/name (0.0.1)'));
        $this->scanner->expects($this->once())
            ->method('scanByPackageIdentifier')
            ->with('author/name');
        $this->expectIterator($this->feed, array($entry));
        $this->reader->read();
    }
    
    /**
     * Apply expectations for an \Iterator on a mock object.
     *
     * @see http://php.net/Iterator
     *
     * @param object $mock    A mock object to apply expectations to.
     * @param array  $content The content the mock will contain and return while iteraring.
     * @param bool   $withKey Whether to add expectations on the key of the iterator.
     *                        Set this to true if you expect a key based loop:
     *                        foreach ($foo as $key => $value)
     * @param int    $counter The method invocation start counter.
     *                        This parameter should be altered when using additional
     *                        expectations before the actual iteration.
     *
     * @return int            The next method invocation counter to add further expectations.
     * @link https://gist.github.com/havvg/2852498
     */
    protected function expectIterator($mock, array $content, $withKey = false, $counter = 0)
    {
        $mock
            ->expects($this->at($counter))
            ->method('rewind')
        ;
 
        foreach ($content as $key => $value) {
            $mock
                ->expects($this->at(++$counter))
                ->method('valid')
                ->will($this->returnValue(true))
            ;
 
            $mock
                ->expects($this->at(++$counter))
                ->method('current')
                ->will($this->returnValue($value))
            ;
 
            if ($withKey) {
                $mock
                    ->expects($this->at(++$counter))
                    ->method('key')
                    ->will($this->returnValue($key))
                ;
            }
 
            $mock
                ->expects($this->at(++$counter))
                ->method('next')
            ;
        }
 
        $mock
            ->expects($this->at(++$counter))
            ->method('valid')
            ->will($this->returnValue(false))
        ;
 
        return ++$counter;
    }
}