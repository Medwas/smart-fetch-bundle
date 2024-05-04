<?php

namespace Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition;

use Iterator;
use Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\Attributes\FilterBy;
use Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\Attributes\LeftJoin;
use Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\Attributes\OrderBy;
use Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\Attributes\Pager;
use Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\Attributes\SmartFetchConditionInterface;

class FieldCondition implements Iterator
{

    /**
     * @var SmartFetchConditionInterface[]
     */
    private array $conditions;

    /**
     * @var LeftJoin[]
     */
    private array $leftJoinConditions = [];
    /**
     * @var array<string, string>
     */
    private array $joinedAliases = [];

    /**
     * @var OrderBy[]
     */
    private array $orderByConditions = [];

    /**
     * @var FilterBy[]
     */
    private array $filterByConditions = [];

    /**
     * @var Pager[]
     */
    private array $pagerConditions = [];
    private int $position = 0;

    private bool $joined = false;

    public function __construct()
    {
    }

    public function current(): SmartFetchConditionInterface
    {
        $condition = $this->conditions[$this->position];
        $condition->setJoined($this->joined);

        return $condition;
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function key(): int
    {
        return $this->position;
    }

    public function valid(): bool
    {
        return isset($this->conditions[$this->position]);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function add(SmartFetchConditionInterface $condition): void
    {
        $condition->setJoined($this->joined);
        $this->conditions[] = $condition;

        match ($condition::class){
            LeftJoin::class => $this->leftJoinConditions[] = $condition,
            OrderBy::class => $this->orderByConditions[] = $condition,
            FilterBy::class => $this->filterByConditions[] = $condition,
            Pager::class => $this->pagerConditions[] = $condition,
        };
    }

    public function getAll(): array
    {
        return $this->conditions;
    }

    public function isJoined(): bool
    {
        return $this->joined;
    }

    public function setJoined(bool $joined): void
    {
        $this->joined = $joined;
    }

    public function getLeftJoinConditions(): array
    {
        return $this->leftJoinConditions;
    }

    public function hasLeftJoin(): bool
    {
        return !empty($this->leftJoinConditions);
    }

    public function getOrderByConditions(): array
    {
        return $this->orderByConditions;
    }
    
    public function hasOrderBy(): bool
    {
        return !empty($this->orderByConditions);
    }

    public function getFilterByConditions(): array
    {
        return $this->filterByConditions;
    }
    
    public function hasFilterBy(): bool
    {
        return !empty($this->filterByConditions);
    }

    public function getPagerConditions(): array
    {
        return $this->pagerConditions;
    }
    
    public function hasPager(): bool
    {
        return !empty($this->pagerConditions);
    }

    public function addJoinedAlias(array $joinedAliases): void
    {
        $this->joinedAliases = array_merge($this->joinedAliases, $joinedAliases);
    }

    /**
     * @return bool
     */
    public function hasConditions(): bool
    {
        return !empty($this->conditions);
    }

    /**
     * @param string $fieldName
     * @return string
     */
    public function getJoinedAliasFromFieldName(string $fieldName): string
    {
        if(key_exists($fieldName, $this->joinedAliases)){
            return $this->joinedAliases[$fieldName];
        }

        return $fieldName;
    }
}