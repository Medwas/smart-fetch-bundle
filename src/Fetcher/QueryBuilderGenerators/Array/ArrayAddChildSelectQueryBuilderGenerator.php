<?php

    namespace Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\Array;

    use Doctrine\ORM\QueryBuilder;
    use Verclam\SmartFetchBundle\Fetcher\Condition\Attributes\Condition;
    use Verclam\SmartFetchBundle\Fetcher\Configuration\Configuration;
    use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
    use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Component;
    use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Composite;

    class ArrayAddChildSelectQueryBuilderGenerator
    {
        private array $maxConfig = [
            SmartFetchObjectManager::ONE_TO_ONE         => 0,
            SmartFetchObjectManager::MANY_TO_MANY       => 0,
            SmartFetchObjectManager::MANY_TO_ONE        => 0,
            SmartFetchObjectManager::ONE_TO_MANY        => 0,
            SmartFetchObjectManager::SCALAR             => 0
        ];

        private array $addedMax = [
            SmartFetchObjectManager::ONE_TO_ONE         => 0,
            SmartFetchObjectManager::MANY_TO_MANY       => 0,
            SmartFetchObjectManager::MANY_TO_ONE        => 0,
            SmartFetchObjectManager::ONE_TO_MANY        => 0,
            SmartFetchObjectManager::SCALAR             => 0
        ];

        public function __construct(
            private readonly Configuration $configuration,
        )
        {
        }

        public function generate(Component $component , QueryBuilder $queryBuilder): QueryBuilder
        {
            if(!($component instanceof Composite))
            {
                return $queryBuilder;
            }

            $this->initMaxConfiguration();

            foreach ($component->getChildren() as $child){
                if($child->isInitialized()){
                    continue;
                }

                if($this->addedMax[$child->getRelationType()] >= $this->maxConfig[$child->getRelationType()]){
                    continue;
                }

                ++$this->addedMax[$child->getRelationType()];

                $this->addSelect($child, $queryBuilder);
                $this->addJoin($child, $queryBuilder);
                $this->addCondition($child, $queryBuilder);
                $child->setIsInitialized(true);
            }

            $this->resetConfig();

            return $queryBuilder;
        }

        private function addSelect(Component $component, QueryBuilder $queryBuilder): QueryBuilder
        {
            return $queryBuilder->addSelect($component->getAlias());
        }

        private function addJoin(Component $component, QueryBuilder $queryBuilder): QueryBuilder
        {
            return $queryBuilder->leftJoin($component->getParent()->getAlias() . '.' . $component->getPropertyName(), $component->getAlias());
        }

        private function addCondition(Component $component, QueryBuilder $queryBuilder): QueryBuilder
        {
            /** @var Condition $condition */
            foreach ($component->getPropertyCondition() as $condition){
                $queryBuilder = $queryBuilder
                    ->andWhere($component->getAlias() . '.' . $condition->property . $condition->operator . $condition->property)
                    ->setParameter($condition->property, $condition->value);
            }
            return $queryBuilder;
        }

        private function initMaxConfiguration(): void
        {
            $maxConfig = $this->configuration->getAll();

            foreach ($maxConfig as $key => $value){
                $this->maxConfig[$key] = $value;
            }

        }

        private function resetConfig(): void
        {
            foreach ($this->addedMax as $key => $value){
                $this->addedMax[$key] = 0;
            }
        }

    }