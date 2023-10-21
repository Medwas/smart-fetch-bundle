<?php

    namespace Verclam\SmartFetchBundle\Attributes;

    #[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
    class SmartFetch implements SmartFetchInterface
    {

        public function __construct(
            private string $queryName,
            private ?string $class = null,
            private array $joinEntities = [],
            private ?string $argumentName = null,
            private ?string $entityManager = null
        ) {
        }

        /**
         * Returns the parameter class name.
         *
         * @return string
         */
        public function getClass(): ?string
        {
            return $this->class;
        }

        /**
         * Sets the parameter class name.
         *
         * @param string $class The parameter class name
         */
        public function setClass(string $class): void
        {
            $this->class = $class;
        }


        /**
         * @return string|null
         */
        public function getArgumentName(): ?string
        {
            return $this->argumentName;
        }

        /**
         * @param mixed $argumentName
         */
        public function setArgumentName(mixed $argumentName): void
        {
            $this->argumentName = $argumentName;
        }

        public function getJoinEntities(): array
        {
            return $this->joinEntities;
        }

        public function setJoinEntities(array $joinEntities): void
        {
            $this->joinEntities = $joinEntities;
        }

        /**
         * @return string|null
         */
        public function getEntityManager(): ?string
        {
            return $this->entityManager;
        }

        /**
         * @param mixed $entityManager
         */
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
    }