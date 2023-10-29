<?php

namespace Verclam\SmartFetchBundle\Attributes;

#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class SmartFetchSerialize implements SmartFetchInterface
{
    private string|int $queryValue;

    public function __construct(
        private readonly string       $queryName,
        private readonly string|array $groups,
        private readonly ?string      $class = null,
        private readonly ?string      $argumentName = null,
        private readonly ?string      $entityManager = null
    )
    {
    }

    public function getQueryValue(): int|string
    {
        return $this->queryValue;
    }

    public function setQueryValue(int|string $queryValue): void
    {
        $this->queryValue = $queryValue;
    }

    public function getGroups(): array
    {
        return is_string($this->groups) ? [$this->groups] : $this->groups;
    }

    public function getEntityManager(): ?string
    {
        return $this->entityManager;
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function getArgumentName(): ?string
    {
        return $this->argumentName;
    }

    public function getQueryName(): string
    {
        return $this->queryName;
    }
}