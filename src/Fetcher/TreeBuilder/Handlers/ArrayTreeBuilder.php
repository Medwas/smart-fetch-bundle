<?php

namespace Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Handlers;

use Doctrine\Persistence\Mapping\ClassMetadata;
use ReflectionException;
use Symfony\Component\Serializer\Annotation\Groups;
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
        //TODO: has to be done in tree association mode
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

    protected function buildTreeSerializationGroups(
        array           &$mappers,
        ClassMetadata   $classMetadata,
        array           &$visited = [],
    ): array
    {
        $result = [];

        $fieldNames = [
            ...$classMetadata->getFieldNames(),
            ...$classMetadata->getAssociationNames()
        ];

        foreach ($fieldNames as $fieldName) {
            if (in_array($fieldName, $visited, true)) {
                continue;
            }

            $reflectionProperty = $classMetadata->getReflectionProperty($fieldName);
            $attribute = $reflectionProperty->getAttributes(Groups::class); // Attribute is not "IS_REPEATABLE" but it works, for now we just take the first one

            if (count($attribute) === 0) {
                continue;
            }

            $argument = $attribute[0]->getArguments()[0]; // Groups has only one argument which can be string|array
            $groups = is_string($argument) ? [$argument] : $argument;

            if (count(array_intersect($mappers, $groups)) === 0) {
                continue;
            }

            //Association
            if($classMetadata->hasAssociation($fieldName)){
                $associationMapping = $classMetadata->getAssociationMapping($fieldName);
                $visited[]          = $associationMapping['mappedBy'];
                $classMetaData      = $this->objectManager->getClassMetadata($associationMapping['targetEntity']);
                $result[$fieldName] = $this->buildTreeSerializationGroups($mappers, $classMetaData, $visited);
                continue;
            }

            //Scalar
            $result[$fieldName] = [];
        }

        return $result;
    }

}