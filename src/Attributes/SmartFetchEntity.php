<?php

namespace Verclam\SmartFetchBundle\Attributes;

use Verclam\SmartFetchBundle\Enum\MappersModeEnum;

#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class SmartFetchEntity extends SmartFetch
{
    private array $mappers;
    public function __construct(
        private MappersModeEnum $mappersMode,
        string|array $mappers,
        string $queryName = null,
        string $class = null,
        string $argumentName = null,
        bool   $collection = false,
        string $entityManager = null,
        string $filterPagerClass = null,
        array  $options = [],
    )
    {
        if(!is_array($mappers)){
            $mappers = [$mappers];
        }
        $this->mappers = $mappers;

        parent::__construct($queryName, $class, $argumentName, $collection, $entityManager, $filterPagerClass, $options);
    }

    public function getMappersMode(): MappersModeEnum
    {
        return $this->mappersMode;
    }

    public function setMappersMode(MappersModeEnum $mappersMode): void
    {
        $this->mappersMode = $mappersMode;
    }

    public function getMappers(): array|string
    {
        return $this->mappers;
    }

    public function setMappers(array|string $mappers): void
    {
        $this->mappers = $mappers;
    }

    public function getType(): string
    {
        return $this->getClass();
    }
}