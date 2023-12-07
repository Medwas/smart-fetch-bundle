<?php

namespace Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Handlers;

use Doctrine\Persistence\Mapping\ClassMetadata;
use JetBrains\PhpStorm\ArrayShape;
use Verclam\SmartFetchBundle\Attributes\SmartFetch;
use Verclam\SmartFetchBundle\Attributes\SmartFetchArray;
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
        $smartFetchArray = SmartFetchArray::expect($smartFetch);
        $mappers         = $smartFetchArray->getMappers();

        return match ($smartFetchArray->getMappersMode()) {
            MappersModeEnum::ENTITY_ASSOCIATIONS    => $this->buildTreeAssociations($mappers, $classMetadata),
            MappersModeEnum::SERIALIZATION_GROUPS   => $this->buildTreeSerializationGroups($mappers, $classMetadata),
            default                                 => throw new \Error('Not implemented')
        };
    }

    #[ArrayShape(['identifier' => 'string', 'associations' => 'array', 'scalars' => 'array'])]
    public function getClassMetadataInfo(ClassMetadata $classMetadata): array
    {
        return [
            'identifier'    => $classMetadata->getIdentifier()[0],
            'associations'  => $classMetadata->getAssociationNames(),
            'scalars'       => $classMetadata->getFieldNames(),
        ];
    }

    abstract protected function buildTreeAssociations(array &$mappers, ClassMetadata $classMetadata): array;

    abstract protected function buildTreeSerializationGroups(array &$mappers, ClassMetadata $classMetadata): array;
}