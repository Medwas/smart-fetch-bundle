<?php

namespace Verclam\SmartFetchBundle\Attributes;

use Verclam\SmartFetchBundle\Enum\MappersModeEnum;

#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class SmartFetchArray extends SmartFetch
{
    public function __construct(
        private MappersModeEnum $mappersMode,
        private string|array $mappers,
        ?string $queryName = null,
        string $class = null,
        string $argumentName = null,
        bool   $isCollection = false,
        string $entityManager = null
    )
    {
        $this->mappersMode->validateMappers($this->mappers);
        parent::__construct($queryName, $class, $argumentName, $isCollection, $entityManager);
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
}