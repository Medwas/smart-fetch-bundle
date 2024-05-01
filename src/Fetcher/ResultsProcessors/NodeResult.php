<?php

namespace Verclam\SmartFetchBundle\Fetcher\ResultsProcessors;

use Doctrine\ORM\QueryBuilder;
class NodeResult
{
    private ?QueryBuilder $queryBuilder;
    private null|array|object $result;
    private bool $hydrated = false;


    public function getQueryBuilder(): ?QueryBuilder
    {
        return $this->queryBuilder;
    }

    public function setQueryBuilder(QueryBuilder $queryBuilder): void
    {
        $this->queryBuilder = $queryBuilder;
    }

    public function getResult(): object|array
    {
        return $this->result;
    }

    public function setResult(object|array $result): void
    {
        $this->result = $result;
    }

    public function isHydrated(): bool
    {
        return $this->hydrated;
    }

    public function setHydrated(bool $hydrated): static
    {
        $this->hydrated = $hydrated;
        return $this;
    }


}