<?php

    namespace Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\Array;

    use Doctrine\ORM\QueryBuilder;
    use Verclam\SmartFetchBundle\Fetcher\Condition\Attributes\Condition;
    use Verclam\SmartFetchBundle\Fetcher\Configuration\Configuration;
    use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
    use Verclam\SmartFetchBundle\Fetcher\PropertyPaths\PropertyPaths;
    use Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\QueryBuilderGeneratorInterface;
    use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Component;
    use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Composite;

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

        public function generate(Component $component , PropertyPaths $paths): QueryBuilder
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

        private function buildComponentQueryBuilder(Component $component, PropertyPaths $paths): QueryBuilder
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

        private function addSelect(Component $component, QueryBuilder $queryBuilder): QueryBuilder
        {
            if($component->getParent()->isRoot()){
                return $queryBuilder;
            }
            
            $parent = $this->lastJoined;

            $identificatorProperty = $parent->getClassMetadata()->getIdentifier();

            if(count($identificatorProperty) > 1){
                throw new \Exception('Composite keys are not supported');
            }

            $identificatorProperty = $identificatorProperty[0];

            $identificatorField = $parent->getAlias() . '.' . $identificatorProperty;
            $identificatorAlias = $parent->getAlias() . '_' . $identificatorProperty;

            return $queryBuilder->addSelect("$identificatorField as $identificatorAlias");
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