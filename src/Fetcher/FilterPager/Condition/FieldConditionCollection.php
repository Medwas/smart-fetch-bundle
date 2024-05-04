<?php

namespace Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition;

use Iterator;

class FieldConditionCollection implements Iterator
{

    /**
     * @var FieldCondition[]
     */
    private array $conditions;

    private int $position = 0;

    public function __construct()
    {
    }

    public function current(): FieldCondition
    {
        return $this->conditions[$this->position];
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

    public function add(FieldCondition $condition): void
    {
        $this->conditions[] = $condition;
    }

    public function getAll(): array
    {
        return $this->conditions;
    }
}