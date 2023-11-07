<?php

namespace Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Handlers;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Verclam\SmartFetchBundle\Attributes\SmartFetch;

interface TreeBuilderInterface
{
    function support(SmartFetch $smartFetch): bool;

    function handle(SmartFetch $smartFetch, ClassMetadata $classMetadata): array;
}