<?php

    namespace Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\Entity;

    use Doctrine\ORM\QueryBuilder;
    use Verclam\SmartFetchBundle\Fetcher\Condition\Attributes\Condition;
    use Verclam\SmartFetchBundle\Fetcher\Configuration\Configuration;
    use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
    use Verclam\SmartFetchBundle\Fetcher\PropertyPaths\PropertyPaths;
    use Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\QueryBuilderGeneratorInterface;
    use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Component;

    class EntityQueryBuilderGenerator implements QueryBuilderGeneratorInterface
    {
        private Component $lastJoined;

        public function __construct(
            private readonly SmartFetchObjectManager            $objectManager,
            private readonly EntityReverseQueryBuilderGenerator $reverseQBGenerator,
            private readonly Configuration                      $configuration,
        )
        {
        }

        public function generate(Component $component , PropertyPaths $paths): QueryBuilder
        {
            $queryBuilder = $this->objectManager->createQueryBuilder()
                ->select($component->getAlias())
                ->from($component->getClassName(), $component->getAlias());
            $this->lastJoined = $component;
            $this->addCondition($component, $queryBuilder);

            $queryBuilder = $this->reverseQBGenerator->generate($component, $paths, $queryBuilder);

            //TODO: MANAGE THE MAX IN THE JOINS
//            if(!($component instanceof Composite))
//            {
//                return $queryBuilder;
//            }

//            foreach ($component->getChildren() as $child){
//                if($child->isInitialized()){
//                    continue;
//                }
//                $this->addSelect($child, $queryBuilder);
//                $this->addJoin($child, $queryBuilder);
//                $this->addCondition($child, $queryBuilder);
//                $child->setIsInitialized(true);
//                $this->lastJoined = $child;
//            }



            return $queryBuilder;
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