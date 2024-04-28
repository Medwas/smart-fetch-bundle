<?php

namespace Verclam\SmartFetchBundle\Attributes;

abstract class SmartFetch
{
    private string|int|null $queryValue;
    public array $enableFilters = [];
    public array $disableFilters = [];

    public function __construct(
        private ?string         $queryName,
        private ?string         $class = null,
        private ?string         $argumentName = null,
        private bool            $collection = false,
        private ?string         $entityManager = null,
        private ?array          $options = [],
    )
    {
     $this->initOptions($options);
    }

    private function initOptions(array $options): void
    {
        //TODO: Validate the type of the options
        if (array_key_exists('enableFilters', $options)) {
            $this->enableFilters = $options['enableFilters'];
        }

        if (array_key_exists('disableFilters', $options)) {
            $this->disableFilters = $options['disableFilters'];
        }
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function setClass(string $class): void
    {
        $this->class = $class;
    }

    public function getArgumentName(): ?string
    {
        return $this->argumentName;
    }

    public function setArgumentName(mixed $argumentName): void
    {
        $this->argumentName = $argumentName;
    }

    public function getEntityManager(): ?string
    {
        return $this->entityManager;
    }

    public function setEntityManager(mixed $entityManager): void
    {
        $this->entityManager = $entityManager;
    }

    public function getQueryName(): ?string
    {
        return $this->queryName;
    }

    public function setQueryName(?string $queryName): void
    {
        $this->queryName = $queryName;
    }

    public function getQueryValue(): int|string|null
    {
        return $this->queryValue;
    }

    public function setQueryValue(int|string|null $queryValue): void
    {
        $this->queryValue = $queryValue;
    }

    public function isCollection(): bool
    {
        return $this->collection;
    }

    public function setIsCollection(bool $collection): void
    {
        $this->collection = $collection;
    }

    public static function expect($object): static
    {
        if (!is_a($object, static::class)) {
            throw new \Error(sprintf('Object must be a %s but got %s', static::class, $object::class));
        }

        return $object;
    }
    
    abstract public function getMappers(): array|string;
    abstract public function getType(): string;

}