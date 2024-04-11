<?php

namespace Verclam\SmartFetchBundle\Fetcher\Visitor;

use Doctrine\ORM\QueryBuilder;
use Verclam\SmartFetchBundle\Attributes\SmartFetch;
use Verclam\SmartFetchBundle\Fetcher\Configuration\Configuration;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Component;

interface SmartFetchVisitorInterface
{
    public function support(SmartFetch $smartFetch): bool;

    /**
     * Generate the query builder for the node
     * fetch the result, and save it in the Node.
     */
    public function fetchResult(Component $component);

/**
     * Start visiting the tree beginning from the root node
     * @param Component $component
     * @return void
     */
    public function visit(Component $component): void;

    /**
     * Join all the node's result to the root node,
     * and update the root node's result
     */
    public function processResults(Component $component): void;
}