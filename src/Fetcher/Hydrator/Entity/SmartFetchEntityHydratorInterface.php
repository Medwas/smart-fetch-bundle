<?php

namespace Verclam\SmartFetchBundle\Fetcher\Hydrator\Entity;

use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\Node;

interface SmartFetchEntityHydratorInterface
{
    public function hydrate(Node $node): void;

    public function support(Node $node): bool;

}