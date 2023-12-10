<?php

    namespace Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component;

    use Error;
    use Verclam\SmartFetchBundle\Fetcher\Condition\Attributes\Condition;
    use Doctrine\Persistence\Mapping\ClassMetadata;
    use Verclam\SmartFetchBundle\Fetcher\Condition\PropertyCondition;
    use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
    use Verclam\SmartFetchBundle\Fetcher\Visitor\SmartFetchVisitorInterface;

    abstract class Component implements ComponentInterface
    {
        protected bool $isRoot;
        private bool $isInitialized = false;
        private bool $hasBeenHydrated = false;
        private ?Composite $parent = null;
        private ClassMetadata $classMetadata;
        private PropertyCondition $propertyCondition;

        private string $alias;
        private string $propertyName;
        private null|array|object $result = null;
        protected array $propertyInformations;

        public function __construct()
        {
            $this->propertyCondition = new PropertyCondition();
        }

        public function isRoot(): bool
        {
            return $this->isRoot;
        }

        public function setParent(Composite $parent): static
        {
            $this->parent = $parent;
            return $this;
        }

        public function getParent(): ?Composite
        {
            return $this->parent;
        }

        public function getClassMetadata(): ClassMetadata
        {
            return $this->classMetadata;
        }

        public function getPropertyCondition(): PropertyCondition
        {
            return $this->propertyCondition;
        }

         public function hasType(int $type): bool
         {
             return $this->propertyInformations['type'] === $type;
         }

         public function isInitialized(): bool
         {
             return $this->isInitialized;
         }

         public function setIsInitialized(bool $isInitialized): static
         {
                $this->isInitialized = $isInitialized;
                return $this;
         }

        public function isScalar(): bool
        {
            return $this->propertyInformations['type'] === SmartFetchObjectManager::SCALAR;
        }

        public function getAlias(): string
        {
            return $this->alias;
        }

        public function setAlias(string $alias): static
        {
            $this->alias = $alias;
            return $this;
        }

        public function setPropertyName(string $propertyName): static
        {
            $this->propertyName = $propertyName;
            return $this;
        }

        public function getPropertyName(): string
        {
            return $this->propertyName;
        }

        public function getReflectionProperty(string $propertyName): \ReflectionProperty
        {
            return $this->classMetadata->getReflectionProperty($propertyName);
        }

        public function getResult(): null|object|array
        {
            return $this->result;
        }

        public function setResult(object|array $result): static
        {
            $this->result = $result;
            return $this;
        }

        public function getParentResult(): null|object|array
        {
            return $this->parent->getResult();
        }

        public function addCondition(Condition $condition): static
        {
            $this->propertyCondition->add($condition);
            return $this;
        }

        public function setClassMetadata(ClassMetadata $classMetadata): Component
        {
            $this->classMetadata = $classMetadata;
            return $this;
        }

        public function setPropertyInformations(array $propertyInformations): static
        {
            $this->propertyInformations = $propertyInformations;
            return $this;
        }

        public function isOwningSide(): bool
        {
            return !$this->propertyInformations['isOwningSide'];
        }

        public function getParentProperty(): string
        {
            return !$this->isOwningSide()
                ? $this->propertyInformations['inversedBy']
                : $this->propertyInformations['mappedBy'];
        }

        public function getRelationType(): int
        {
            return $this->propertyInformations['type'];
        }

        public function getClassName(): string
        {
            return $this->classMetadata->getName();
        }

        abstract public function handle(SmartFetchVisitorInterface $visitor): void;
        abstract public function isComposite(): bool;

        public function hasBeenHydrated(): bool
        {
            return $this->hasBeenHydrated;
        }

        public function setHasBeenHydrated(bool $hasBeenHydrated): static
        {
            $this->hasBeenHydrated = $hasBeenHydrated;
            return $this;
        }

        public static function expect($object): static
        {
            if (!is_a($object, static::class)) {
                throw new Error(sprintf('Object must be a %s but got %s', static::class, $object::class));
            }

            return $object;
        }

        public function __toString(): string
        {
            return $this->propertyName;
        }
    }