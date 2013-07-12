<?php
/**
 * FeedReader.php
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */

namespace Metagist\Worker;

use Zend\Feed\Reader\Feed\FeedInterface;
use Zend\Feed\Reader\Entry\EntryInterface;
use Metagist\Worker\Scanner\PackageScanner;
use Psr\Log\LoggerInterface;

/**
 * Class which scans packages based on packagist feed updates.
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
class FeedReader
{
    /**
     * Feed
     * 
     * @var \Zend\Feed\Reader\Feed\FeedInterface 
     */
    private $feed;
    
    /**
     * package scanner.
     * 
     * @var \Metagist\Worker\Scanner\PackageScanner
     */
    private $scanner;
    
    /**
     * logger instance
     * 
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    
    /**
     * Constructor.
     * 
     * @param \Zend\Feed\Reader\Feed\FeedInterface $feed
     * @param \Metagist\Worker\Scanner\PackageScanner $scanner
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(FeedInterface $feed, PackageScanner $scanner, LoggerInterface $logger = null)
    {
        $this->feed    = $feed;
        $this->scanner = $scanner;
        $this->logger  = $logger;
    }
    
    /**
     * Read the latest feed entries.
     * 
     */
    public function read()
    {
        foreach ($this->feed as $entry) {
            $package = $this->createDummyPackageFromEntry($entry);
            if ($package === null) {
                $this->logger && $this->getLogger()->warning('Feed: Skipped entry ' . $entry->getTitle());
                continue;
            }
            
            $this->logger && $this->logger->info('Feed: Trying to scan ' . $entry->getTitle());
            try {
                $infos = $this->scanner->scanByPackageIdentifier($package->getIdentifier());
                foreach ($infos as $info) {
                    $this->scanner->pushInfo($info, $package->getAuthor(), $package->getName());
                }
            } catch (\Exception $exception) {
                $this->logger && $this->logger->error('Feed: Error while trying to scan ' . $package->getIdentifier() . ': ' . $exception->getMessage());
            }
        }
    }
    
    /**
     * Creates a dummy package.
     * 
     * @param \Zend\Feed\Reader\Entry\EntryInterface $entry
     * @return \Metagist\Package|null
     */
    protected function createDummyPackageFromEntry(EntryInterface $entry)
    {
        $title      = $entry->getTitle();
        $tmp        = explode(' ', $title);
        $identifier = $tmp[0];
        $version    = trim($tmp[1], '()');
        
        if (!\Metagist\Validator::isValidIdentifier($identifier)) {
            return null;
        }
        
        $package = new \Metagist\Package($identifier);
        $package->setVersions(array($version));
        return $package;
    }
}