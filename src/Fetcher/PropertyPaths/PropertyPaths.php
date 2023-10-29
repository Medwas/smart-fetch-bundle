<?php

    namespace Verclam\SmartFetchBundle\Fetcher\PropertyPaths;

    use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
    use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Component;

    class PropertyPaths implements \Iterator
    {

        private int $position = 0;
        /**
         * @var Component[]
         */
        private array $propertyPaths = [];

        public function add(Component $component): static
        {
            $component->setIsInitialized(true);
            $this->propertyPaths[] = $component;
            return $this;
        }

        public function removeLast(): static
        {
            array_pop($this->propertyPaths);
            return $this;
        }

        public function getAll(): array
        {
            return $this->propertyPaths;
        }

        public function get(int $index): Component
        {
            return $this->propertyPaths[$index];
        }

        #[\ReturnTypeWillChange]
        public function current(): Component
        {
            return $this->propertyPaths[$this->position];
        }

        public function next(): void
        {
            --$this->position;
        }

        public function key(): int
        {
            return $this->position;
        }

        public function valid(): bool
        {
            return isset($this->propertyPaths[$this->position]);
        }

        public function rewind(): void
        {
            $this->position = $this->count() - 1;
        }

        public function count(): int
        {
            return count($this->propertyPaths);
        }

    }