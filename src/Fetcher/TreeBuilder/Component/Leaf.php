<?php

namespace Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Verclam\SmartFetchBundle\Fetcher\Visitor\SmartFetchVisitorInterface;

class Leaf extends Component
{
    public function __construct(bool $root = false)
    {
        $this->isRoot = $root;
        parent::__construct();
    }

    public function isComposite(): bool
    {
        return false;
    }

    public function handle(SmartFetchVisitorInterface $visitor): void
    {
        $visitor->fetchResult($this);
    }
}