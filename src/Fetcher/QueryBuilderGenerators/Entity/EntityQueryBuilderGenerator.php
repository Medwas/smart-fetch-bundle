<?php

    namespace Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\Entity;

    use Doctrine\ORM\QueryBuilder;
    use Verclam\SmartFetchBundle\Fetcher\Condition\Attributes\Condition;
    use Verclam\SmartFetchBundle\Fetcher\History\HistoryPaths;
    use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
    use Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\QueryBuilderGeneratorInterface;
    use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Component;

    class EntityQueryBuilderGenerator implements QueryBuilderGeneratorInterface
    {
        private Component $lastJoined;

        public function __construct(
            private readonly SmartFetchObjectManager                    $objectManager,
            private readonly EntityReverseQueryBuilderGenerator         $reverseQBGenerator,
            private readonly EntityAddChildSelectQueryBuilderGenerator  $addChildSelectQueryBuilderGenerator,
        )
        {
        }

        public function generate(Component $component , HistoryPaths $paths): QueryBuilder
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
                ->select($component->getAlias())
                ->from($component->getClassName(), $component->getAlias());

            $this->lastJoined = $component;

            $this->addCondition($component, $queryBuilder);
            return $queryBuilder;
        }

        private function buildComponentQueryBuilder(Component $component, HistoryPaths $paths): QueryBuilder
        {
            $parent = $component->getParent();

            $queryBuilder = $this->objectManager->createQueryBuilder()
                ->select($parent->getAlias())
                ->from($parent->getClassName(), $parent->getAlias());

            $this->lastJoined = $parent;

            $this->addSelect($component, $queryBuilder);
            $this->addJoin($component, $queryBuilder);
            $this->addCondition($parent, $queryBuilder);

            return $this->reverseQBGenerator->generate($parent, $paths, $queryBuilder);
        }

        private function addSelect(Component $component, QueryBuilder $queryBuilder): QueryBuilder
        {
            return $queryBuilder->addSelect($component->getAlias());
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