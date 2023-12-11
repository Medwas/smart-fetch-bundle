<?php

    namespace Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\Array;

    use Doctrine\ORM\QueryBuilder;
    use Exception;
    use Verclam\SmartFetchBundle\Fetcher\Condition\Attributes\Condition;
    use Verclam\SmartFetchBundle\Fetcher\History\HistoryPaths;
    use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
    use Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\QueryBuilderGeneratorInterface;
    use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Component;

    class ArrayQueryBuilderGenerator implements QueryBuilderGeneratorInterface
    {
        private Component $lastJoined;

        public function __construct(
            private readonly SmartFetchObjectManager                    $objectManager,
            private readonly ArrayReverseQueryBuilderGenerator          $reverseQBGenerator,
            private readonly ArrayAddChildSelectQueryBuilderGenerator   $addChildSelectQueryBuilderGenerator,
        )
        {
        }

        public function generate(Component $component, HistoryPaths $paths): QueryBuilder
        {
            $queryBuilder = match($component->isRoot()) {
                true        => $this->buildRootQueryBuilder($component),
                false       => $this->buildComponentQueryBuilder($component, $paths)
            };

            return $this->addChildSelectQueryBuilderGenerator
                ->generate($component, $queryBuilder);
        }

        private function buildRootQueryBuilder(Component $component): QueryBuilder
        {
            $queryBuilder = $this->objectManager->createQueryBuilder()
                ->select($this->buildScalarSelect($component))
                ->from($component->getClassName(), $component->getAlias());

            $this->lastJoined = $component;

            $this->addCondition($component, $queryBuilder);
            return $queryBuilder;
        }

        /**
         * @throws Exception
         */
        private function buildComponentQueryBuilder(Component $component, HistoryPaths $paths): QueryBuilder
        {
            $parent = $component->getParent();

            $queryBuilder = $this->objectManager->createQueryBuilder()
                ->select($this->buildScalarSelect($component))
                ->from($parent->getClassName(), $parent->getAlias());

            $this->lastJoined = $parent;

            $this->addSelect($component, $queryBuilder);
            $this->addJoin($component, $queryBuilder);
            $this->addCondition($parent, $queryBuilder);

            return $this->reverseQBGenerator->generate($parent, $paths, $queryBuilder);
        }

        private function buildScalarSelect(Component $component): string
        {
            $alias = $component->getAlias();
            $selector = '';

            /** @var Component $child */
            foreach ($component->getChildren() as $child){
                if(!$child->isScalar()){
                    continue;
                }

                if(strlen($selector) > 0){
                    $selector .= ', ';
                }

                $selector .= $alias . '.' . $child->getPropertyName();
                $child->setHasBeenHydrated(true);
                $child->setIsInitialized(true);
            }

            return $selector;
        }

        /**
         * @throws Exception
         */
        private function addSelect(Component $component, QueryBuilder $queryBuilder): QueryBuilder
        {
            if($component->getParent()->isRoot()){
                return $queryBuilder;
            }
            
            $parent = $this->lastJoined;

            $identifierProperty = $parent->getClassMetadata()->getIdentifier();

            if(count($identifierProperty) > 1){
                throw new Exception('Composite keys are not supported, Doctrine\'s best practice, says that it is better to avoid using it');
            }

            $identifierProperty = $identifierProperty[0];

            $identifierField = $parent->getAlias() . '.' . $identifierProperty;
            $identifierAlias = $parent->getAlias() . '_' . $identifierProperty;

            return $queryBuilder->addSelect("$identifierField as $identifierAlias");
        }

        private function addJoin(Component $component, QueryBuilder $queryBuilder): QueryBuilder
        {
            return $queryBuilder->leftJoin($this->lastJoined->getAlias() . '.' . $component->getPropertyName(), $component->getAlias());
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
    }