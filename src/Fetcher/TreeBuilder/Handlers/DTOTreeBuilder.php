<?php

namespace Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Handlers;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Verclam\SmartFetchBundle\Attributes\SmartFetch;
use Verclam\SmartFetchBundle\Attributes\SmartFetchDTO;

class DTOTreeBuilder extends AbstractTreeBuilder
{
    function support(SmartFetch $smartFetch): bool
    {
        return $smartFetch instanceof SmartFetchDTO;
    }

    protected function buildTreeAssociations(array &$mappers, ClassMetadata $classMetadata, bool $isRoot = false): array
    {
        return [];
    }

    protected function buildTreeSerializationGroups(array &$mappers, ClassMetadata $classMetadata): array
    {
        return [];
    }


}