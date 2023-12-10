<?php

namespace Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Handlers;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Verclam\SmartFetchBundle\Attributes\SmartFetch;

interface TreeBuilderInterface
{
    function support(SmartFetch $smartFetch): bool;

    /**
     * Build the array the represent all the fields to fetch
     * Depending on the serliazation group, properyPath, or DTO provided.
     * This Array will be used the build the tree
     * @param SmartFetch $smartFetch
     * @param ClassMetadata $classMetadata
     * @return array
     */
    function handle(SmartFetch $smartFetch, ClassMetadata $classMetadata): array;
}