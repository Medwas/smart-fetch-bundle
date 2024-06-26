<?php

namespace Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Handlers;

use Doctrine\Persistence\Mapping\ClassMetadata;
use JetBrains\PhpStorm\ArrayShape;
use Verclam\SmartFetchBundle\Attributes\SmartFetch;
use Verclam\SmartFetchBundle\Enum\MappersModeEnum;
use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;

abstract class AbstractTreeBuilder implements TreeBuilderInterface
{
    public function __construct(
        protected readonly SmartFetchObjectManager $objectManager,
    )
    {
    }

    public function handle(SmartFetch $smartFetch, ClassMetadata $classMetadata): array
    {
        $smartFetch      = SmartFetch::expect($smartFetch);
        $mappers         = $smartFetch->getMappers();

        return match ($smartFetch->getMappersMode()) {
            MappersModeEnum::ENTITY_ASSOCIATIONS    => $this->buildTreeAssociations($mappers, $classMetadata),
            MappersModeEnum::SERIALIZATION_GROUPS   => $this->buildTreeSerializationGroups($mappers, $classMetadata),
            default                                 => throw new \Error('Not implemented')
        };
    }

    #[ArrayShape(['identifier' => 'string', 'associations' => 'array', 'scalars' => 'array'])]
    public function getClassMetadataInfo(ClassMetadata $classMetadata): array
    {
//        $scalars = [];
//        foreach($classMetadata->getFieldNames() as $key => $fieldName){
//            $scalars[$fieldName] = [];
//        }
        return [
            'identifier'    => $classMetadata->getIdentifier()[0],
            'associations'  => $classMetadata->getAssociationNames(),
            'scalars'       => $classMetadata->getFieldNames(),
        ];
    }

    abstract protected function buildTreeAssociations(array &$mappers, ClassMetadata $classMetadata): array;

    abstract protected function buildTreeSerializationGroups(array &$mappers, ClassMetadata $classMetadata): array;
}