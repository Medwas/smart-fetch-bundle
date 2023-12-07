<?php

namespace Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Handlers;

use Doctrine\ORM\Mapping\MappingException;
use Doctrine\Persistence\Mapping\ClassMetadata;
use ReflectionException;
use Verclam\SmartFetchBundle\Attributes\SmartFetch;
use Verclam\SmartFetchBundle\Attributes\SmartFetchArray;

class ArrayTreeBuilder extends AbstractTreeBuilder
{
    function support(SmartFetch $smartFetch): bool
    {
        return $smartFetch instanceof SmartFetchArray;
    }

    /**
     * @throws ReflectionException|MappingException
     */
    protected function buildTreeAssociations(array &$mappers, ClassMetadata $classMetadata): array
    {
        $result     = [];


        [
            'identifier'    => $identifier,
            'associations'  => $associations,
            'scalars'       => $scalars
        ] = $this->getClassMetadataInfo($classMetadata);

        foreach ($mappers as $mapper) {
            $parent = $mapper;
            $child  = null;

            if (str_contains($mapper, '.')) {
                [$parent, $child] = explode('.', $mapper);
            }

            $field = $child ?? $parent;

            if (!$classMetadata->hasAssociation($field)) {
                continue;
            }

            $associationMapping     = $classMetadata->getAssociationMapping($field);

            $targetEntityMetadata   = $this->objectManager->getClassMetadata($associationMapping['targetEntity']);
            $mappers                = array_diff($mappers, [$mapper]);
            $result[$field]        = [
                'identifier'    => $identifier,
                'scalars'       => $scalars,
                'associations'  => $this->buildTreeAssociations($mappers, $targetEntityMetadata)
            ];
        }

        return $result;
    }

    protected function buildTreeSerializationGroups(array &$mappers, ClassMetadata $classMetadata): array
    {
        return [];
    }

}