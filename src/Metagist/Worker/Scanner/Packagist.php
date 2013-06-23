<?php
namespace Metagist\Worker\Scanner;

use \Packagist\Api\Client as PackagistClient;
use Metagist\MetaInfo;

/**
 * Scanner for packagist.
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
class Packagist extends Base implements ScannerInterface
{
    /**
     * packagist api client
     * 
     * @var PackagistClient 
     */
    private $packagistClient;
    
    /**
     * Fetches metainfos from a packagist package.
     * 
     * @param \Metagist\Package $package
     * @return \Metagist\MetaInfo[]
     */
    public function scan(\Metagist\Package $package)
    {
        $packagistPackage = $this->getPackagistClient()->get($package->getIdentifier());
        $metainfos     = array();
        $versions      = $packagistPackage->getVersions();
        /* @var $firstVersion \Packagist\Api\Result\Package\Version */
        $firstVersion  = current($versions);
        
        if ($firstVersion != false) {
            $versionString = $firstVersion->getVersion();
            $repository    = $packagistPackage->getRepository();
            $metainfos = array(
                MetaInfo::fromValue(Metainfo::REPOSITORY, $repository, $versionString),
                MetaInfo::fromValue(MetaInfo::HOMEPAGE, $firstVersion->getHomepage(), $versionString),
                MetaInfo::fromValue(MetaInfo::MAINTAINERS, count($packagistPackage->getMaintainers()), $versionString),
                MetaInfo::fromValue(MetaInfo::REQUIRES, count($firstVersion->getRequire()), $versionString),
                MetaInfo::fromValue(MetaInfo::REQUIRES_DEV, count($firstVersion->getRequireDev()), $versionString)
            );
            
            $licenses = $firstVersion->getLicense();
            if (is_array($licenses)) {
                $metainfos[] = MetaInfo::fromValue(MetaInfo::LICENSE, implode(' ', $licenses), $versionString);
            }
        }
        
        return $metainfos;
    }

    /**
     * Inject the packagist client.
     * 
     * @param \Packagist\Api\Client $client
     */
    public function setPackagistClient(PackagistClient $client)
    {
        $this->packagistClient = $client;
    }
    
    /**
     * Returns the packagist api client.
     * 
     * @return \Packagist\Api\Client
     */
    protected function getPackagistClient()
    {
        if ($this->packagistClient === null) {
            $client = new PackagistClient();
            return $client;
        }
        
        return $this->packagistClient;
    }
}
