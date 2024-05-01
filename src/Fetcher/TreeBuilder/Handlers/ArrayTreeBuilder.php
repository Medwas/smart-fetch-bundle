<?php

namespace Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Handlers;

use Doctrine\ORM\Mapping\MappingException;
use Doctrine\Persistence\Mapping\ClassMetadata;
use ReflectionException;
use Symfony\Component\Serializer\Annotation\Groups;
use Verclam\SmartFetchBundle\Attributes\SmartFetch;
use Verclam\SmartFetchBundle\Attributes\SmartFetchArray;

/**
 * Construct the array relation tree in case where we need
 * an array as result, for this case we need to have
 * information about the scalar property "string, int ...etc"
 * because they will not be fetched by default.
 */
class ArrayTreeBuilder extends AbstractTreeBuilder
{
    function support(SmartFetch $smartFetch): bool
    {
        return $smartFetch instanceof SmartFetchArray;
    }

    /**
     * @throws ReflectionException|MappingException
     * @throws MappingException
     */
    protected function buildTreeAssociations(
        array           &$mappers,
        ClassMetadata   $classMetadata,
        ?string         $associationParentFieldName = null,
    ): array
    {
        //TODO: has to be done in tree association mode
        $result     = [];

        [
            'identifier'    => $identifier,
            'associations'  => $associations,
            'scalars'       => $scalars
        ] = $this->getClassMetadataInfo($classMetadata);

        $arrayScalars = [];

        foreach($scalars as $key => $scalarFieldName){
            $arrayScalars[$scalarFieldName] = [];
        }

        foreach ($mappers as $mapper) {
            $parentFieldName = $mapper;
            $childFieldName  = null;

            if (str_contains($mapper, '.')) {
                [$parentFieldName, $childFieldName] = explode('.', $mapper);
            }

            if(null === $childFieldName && null !== $associationParentFieldName){
                continue;
            }

            $currentFieldName = $childFieldName ?? $parentFieldName;

            if (!$classMetadata->hasAssociation($currentFieldName)) {
                continue;
            }

            if(null !== $childFieldName &&
                $parentFieldName !== $associationParentFieldName
            ){
                continue;
            }

            $childAssociationMapping    = $classMetadata
                ->getAssociationMapping($currentFieldName);

            $targetEntityMetadata       = $this->objectManager
                ->getClassMetadata($childAssociationMapping['targetEntity']);

            $mappers                    = array_diff($mappers, [$mapper]);
            $result[$currentFieldName]  = $this->buildTreeAssociations(
                    $mappers,
                    $targetEntityMetadata,
                    associationParentFieldName: $currentFieldName
                );
        }

        return [
            ...$arrayScalars,
            ...$result
        ];
    }

    protected function buildTreeSerializationGroups(
        array           &$mappers,
        ClassMetadata   $classMetadata,
        array           &$visited = [],
    ): array
    {
        $result = [];

        [
            'associations'  => $associations,
            'scalars'       => $scalars
        ] = $this->getClassMetadataInfo($classMetadata);

        $fieldNames = [...$scalars, ...$associations];

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