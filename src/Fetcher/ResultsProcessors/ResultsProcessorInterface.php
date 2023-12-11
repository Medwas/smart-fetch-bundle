<?php

namespace Verclam\SmartFetchBundle\Fetcher\ResultsProcessors;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Component;

interface ResultsProcessorInterface
{
    /**
     * This function will be first called with the root node
     * and it will be called recursively with every non scalar child node,
     * the objectives of this function is to join the result of every child node
     * with the result of the root node.
     */
    public function processResult(Component $component, array &$result = []): array;
}