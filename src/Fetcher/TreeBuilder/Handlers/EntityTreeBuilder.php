<?php

namespace Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Handlers;

use Doctrine\ORM\Mapping\MappingException;
use Doctrine\Persistence\Mapping\ClassMetadata;
use ReflectionException;
use Symfony\Component\Serializer\Annotation\Groups;
use Verclam\SmartFetchBundle\Attributes\SmartFetch;
use Verclam\SmartFetchBundle\Attributes\SmartFetchEntity;

/**
 * Construct the array relation tree in case where we need
 * an Entity as result, for this case we do not need to have
 * information about the scalar property "string, int ...etc"
 * because they will be fetched by default.
 */
class EntityTreeBuilder extends AbstractTreeBuilder
{
    function support(SmartFetch $smartFetch): bool
    {
        return $smartFetch instanceof SmartFetchEntity;
    }

    protected function buildTreeAssociations(array &$mappers, ClassMetadata $classMetadata): array
    {
        $joinEntities = $this->orderJoinEntities($mappers);
        return $this->buildArrayTree($joinEntities, isRoot: true);
    }

    /**
     * @throws ReflectionException
     */
    protected function buildTreeSerializationGroups(array &$mappers, ClassMetadata $classMetadata): array
    {
       return $this->buildArrayTreeForSerialization($mappers, $classMetadata);
    }

    private function orderJoinEntities(array $joinEntities): array
    {
        return array_reduce($joinEntities, function (array $carry, string $joinEntity) {
            $entities = explode('.', $joinEntity);

            if (count($entities) !== 1) {
                $carry[] = $joinEntity;
                return $carry;
            }

            array_splice($carry, 0,0, $joinEntity);
            return $carry;
        }, []);
    }

    private function buildArrayTree(array &$joinEntities, array &$parent = [], bool $isRoot = false): array
    {
        foreach ($joinEntities as $key => $joinEntity) {
            if (count($joinEntities) === 0) {
                return $parent;
            }

            $entities = explode('.', $joinEntity);

            if (count($entities) === 1 && $isRoot = true) {
                [$field] = $entities;
                $parent[$field] = [];
                unset($joinEntities[$key]);
                $parent = $this->buildArrayTree($joinEntities, $parent);
                continue;
            }

            if (count($entities) > 2) {
                throw new \RuntimeException('Invalid expression, you should not have more than this : field.subfield');
            }

            [$field, $subField] = $entities; // field.subField

            if (key_exists($field, $parent)) {
                $parent[$field][$subField] = [];
                unset($joinEntities[$key]);
                $parent[$field] = $this->buildArrayTree($joinEntities, $parent[$field]);
                continue;
            }

            foreach ($parent as &$parentValue) {
               $this->buildArrayTree($joinEntities, $parentValue);
            }

        }

        return $parent;
    }

    /**
     * @throws ReflectionException
     * @throws MappingException
     */
    private function buildArrayTreeForSerialization(
        array         $serializeGroups,
        ClassMetadata $metadata,
        array         &$visited = [],
    ): array
    {
        $result = [];

        foreach ($metadata->getAssociationNames() as $associationName) {
            if (in_array($associationName, $visited, true)) {
                continue;
            }

            $reflectionProperty = $metadata->getReflectionProperty($associationName);
            $attribute = $reflectionProperty->getAttributes(Groups::class); // Attribute is not "IS_REPEATABLE" but it works, for now we just take the first one

            if (count($attribute) === 0) {
                continue;
            }

            $argument = $attribute[0]->getArguments()[0]; // Groups has only one argument which can be string|array
            $groups = is_string($argument) ? [$argument] : $argument;

            if (count(array_intersect($serializeGroups, $groups)) === 0) {
                continue;
            }

            $associationMapping = $metadata->getAssociationMapping($associationName);
            $visited[] = $associationMapping['mappedBy'];
            $classMetaData = $this->objectManager->getClassMetadata($associationMapping['targetEntity']);
            $result[$associationName] = $this->buildArrayTreeForSerialization($serializeGroups, $classMetaData, $visited);
        }

        return $result;
    }
}