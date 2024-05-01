<?php

namespace Verclam\SmartFetchBundle\Fetcher\Hydrator\Array;

use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\Node;

interface SmartFetchArrayHydratorInterface
{
    public function hydrate(Node $node, array &$parentResults): void;

    public function support(Node $node): bool;

}