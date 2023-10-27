<?php

    namespace Verclam\SmartFetchBundle\Fetcher\PropertyPaths;

    use Verclam\SmartFetchBundle\Fetcher\Condition\PropertyCondition;
    use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Component;

    class PropertyInfo
    {
        private string $alias;
        private string $property;
        private string $className;
        private ?PropertyCondition $condition;

        public function __construct(Component $component)
        {
            $this->alias = $component->getAlias();
            $this->property = $component->getPropertyName();
            $this->condition = $component->getPropertyCondition();
            $this->className = $component->getClassName();
        }

        public function getAlias(): string
        {
            return $this->alias;
        }

        public function setAlias(string $alias): void
        {
            $this->alias = $alias;
        }

        public function getProperty(): string
        {
            return $this->property;
        }

        public function setProperty(string $property): void
        {
            $this->property = $property;
        }

        public function getCondition(): PropertyCondition
        {
            return $this->condition;
        }

        public function setCondition(PropertyCondition $condition): void
        {
            $this->condition = $condition;
        }

        public function getClassName(): string
        {
            return $this->className;
        }


    }