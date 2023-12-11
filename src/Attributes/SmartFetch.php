<?php

namespace Verclam\SmartFetchBundle\Attributes;

abstract class SmartFetch
{
    private  string|int $queryValue;

    public function __construct(
        private string          $queryName,
        private ?string         $class = null,
        private ?string         $argumentName = null,
        private bool            $isCollection = false,
        private ?string         $entityManager = null
    )
    {
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

    public function getQueryName(): string
    {
        return $this->queryName;
    }

    public function setQueryName(string $queryName): void
    {
        $this->queryName = $queryName;
    }

    public function getQueryValue(): int|string
    {
        return $this->queryValue;
    }

    public function setQueryValue(int|string $queryValue): void
    {
        $this->queryValue = $queryValue;
    }

    public function isCollection(): bool
    {
        return $this->isCollection;
    }

    public function setIsCollection(bool $isCollection): void
    {
        $this->isCollection = $isCollection;
    }

    public static function expect($object): static
    {
        if (!is_a($object, static::class)) {
            throw new \Error(sprintf('Object must be a %s but got %s', static::class, $object::class));
        }

        return $object;
    }
    
    abstract public function getMappers(): array|string;
}