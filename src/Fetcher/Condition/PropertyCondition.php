<?php

    namespace Verclam\SmartFetchBundle\Fetcher\Condition;

    use Verclam\SmartFetchBundle\Fetcher\Condition\Attributes\Condition;

    class PropertyCondition implements \Iterator
    {

        /**
         * @var Condition[]
         */
        private array $conditions;

        private int $position = 0;

        public function __construct()
        {
        }

        public function current(): mixed
        {
            return $this->conditions[$this->position];
        }

        public function next(): void
        {
            ++$this->position;
        }

        public function key(): mixed
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

        public function add(Condition $condition): void
        {
            $this->conditions[] = $condition;
        }

        public function getAll(): array
        {
            return $this->conditions;
        }
    }