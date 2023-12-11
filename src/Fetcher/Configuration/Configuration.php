<?php

namespace Verclam\SmartFetchBundle\Fetcher\Configuration;

use Verclam\SmartFetchBundle\Enum\FetchModeEnum;
use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;

class Configuration
{
    public const ENTITY_FETCH_MODE = 'entity';
    public const ARRAY_FETCH_MODE = 'array';
    public const ENTITIES_FETCH_MODE = 'entities';
    public const ARRAYS_FETCH_MODE = 'arrays';
    public const DTO_FETCH_MODE = 'dto';

    private array $defaultOptions = [];
    private FetchModeEnum $fetchMode;

    //TODO: add variable from yaml or from smartFetchAttribute
    public function __construct(
        private readonly int $maxManyToMany     = 0,
        private readonly int $maxOneToMany      = 0,
        private readonly int $maxManyToOne      = 0,
        private readonly int $maxOneToOne       = 0,
        private readonly int $maxScalar         = PHP_INT_MAX,
    )
    {
        $this->defaultOptions['maxManyToMany']  = $this->maxManyToMany;
        $this->defaultOptions['maxOneToMany']   = $this->maxOneToMany;
        $this->defaultOptions['maxManyToOne']   = $this->maxManyToOne;
        $this->defaultOptions['maxOneToOne']    = $this->maxOneToOne;
        $this->defaultOptions['maxScalar']      = $this->maxScalar;
    }

    public function configure(array $options): void
    {
        foreach ($options as $key => $value) {
            $this->$key = $value;
        }
    }

    public function getMaxByType(int|string $relationType): int
    {
        return match ($relationType) {
            SmartFetchObjectManager::MANY_TO_MANY   => $this->maxManyToMany,
            SmartFetchObjectManager::MANY_TO_ONE    => $this->maxOneToMany,
            SmartFetchObjectManager::ONE_TO_MANY    => $this->maxManyToOne,
            SmartFetchObjectManager::ONE_TO_ONE     => $this->maxOneToOne,
            SmartFetchObjectManager::SCALAR         => $this->maxScalar,
            default => throw new \Error('Invalid relation type: ' . $relationType),
        };
    }

    public function getAll(): array
    {
        return [
            SmartFetchObjectManager::MANY_TO_MANY   => $this->maxManyToMany,
            SmartFetchObjectManager::MANY_TO_ONE    => $this->maxOneToMany,
            SmartFetchObjectManager::ONE_TO_MANY    => $this->maxManyToOne,
            SmartFetchObjectManager::ONE_TO_ONE     => $this->maxOneToOne,
            SmartFetchObjectManager::SCALAR         => $this->maxScalar,
        ];
    }

    public function getMaxManyToMany(): int
    {
        return $this->maxManyToMany;
    }

    public function getMaxOneToMany(): int
    {
        return $this->maxOneToMany;
    }

    public function getMaxManyToOne(): int
    {
        return $this->maxManyToOne;
    }

    public function getMaxOneToOne(): int
    {
        return $this->maxOneToOne;
    }

    public function getMaxScalar(): int
    {
        return $this->maxScalar;
    }

    public function reset(): void
    {
        foreach ($this->defaultOptions as $key => $value) {
            $this->$key = $value;
        }
    }

    public function getFetchMode(): FetchModeEnum
    {
        return $this->fetchMode;
    }

    public function hasFetchMode(FetchModeEnum $fetchMode): bool
    {
        return $this->fetchMode === $fetchMode;
    }


}