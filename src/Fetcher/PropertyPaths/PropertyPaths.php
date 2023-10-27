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
//        private array $maxConfig = [
//            SmartFetchObjectManager::ONE_TO_ONE => 0,
//            SmartFetchObjectManager::MANY_TO_MANY => 0,
//            SmartFetchObjectManager::MANY_TO_ONE => 0,
//            SmartFetchObjectManager::ONE_TO_MANY => 0,
//            SmartFetchObjectManager::SCALAR => 0
//        ];

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

//        public function getCurrentAddedRelationType(int $relationType): int
//        {
//            return $this->maxConfig[$relationType];
//        }
//
//        public function getCurrentOneToOne(): int
//        {
//            return $this->maxConfig[SmartFetchObjectManager::ONE_TO_ONE];
//        }
//
//        public function getCurrentManyToMany(): int
//        {
//            return $this->maxConfig[SmartFetchObjectManager::MANY_TO_MANY];
//        }
//
//        public function getCurrentManyToOne(): int
//        {
//            return $this->maxConfig[SmartFetchObjectManager::MANY_TO_ONE];
//        }
//
//        public function getCurrentOneToMany(): int
//        {
//            return $this->maxConfig[SmartFetchObjectManager::ONE_TO_MANY];
//        }
//
//        public function getCurrentScalar(): int
//        {
//            return $this->maxConfig[SmartFetchObjectManager::SCALAR];
//        }
//
//        public function getCurrentCountAdded(): int
//        {
//            return array_reduce($this->maxConfig, fn($carry, $item) => $carry + $item, 0);
//        }
//
//        public function recoverLastState(): void
//        {
//            array_splice($this->propertyPaths, -$this->getCurrentCountAdded());
//            $this->maxConfig = [
//                SmartFetchObjectManager::ONE_TO_ONE => 0,
//                SmartFetchObjectManager::MANY_TO_MANY => 0,
//                SmartFetchObjectManager::MANY_TO_ONE => 0,
//                SmartFetchObjectManager::ONE_TO_MANY => 0,
//                SmartFetchObjectManager::SCALAR => 0
//            ];
//        }
    }