<?php
namespace Metagist\Worker\Scanner;

use Metagist\Worker\Application;

/**
 * Interface for metainfo providers.
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
interface ScannerInterface
{
    /**
     * Constructor requires the application instance, since it is the service locator.
     * 
     * @param \Metagist\Worker\Application $application
     */
    public function __construct(Application $application);
    
    /**
     * Returns a collection of retrieved metainfos.
     * 
     * @param \Metagist\Package $package
     * @return \Metagist\MetaInfo[]
     */
    public function scan(\Metagist\Package $package);
}