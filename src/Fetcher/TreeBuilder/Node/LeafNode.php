<?php

namespace Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Verclam\SmartFetchBundle\Fetcher\ResultsProcessors\NodeResult;
use Verclam\SmartFetchBundle\Fetcher\Visitor\SmartFetchVisitorInterface;

class LeafNode extends Node
{
    public function __construct()
    {
        parent::__construct();
    }

    public function handle(SmartFetchVisitorInterface $visitor): void
    {
        $visitor->fetchResult($this);
    }


    public function isCollection(): bool
    {
        return false;
    }
}