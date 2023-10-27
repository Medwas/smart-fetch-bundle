<?php

    namespace Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\Entity;

    use Doctrine\ORM\QueryBuilder;
    use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
    use Verclam\SmartFetchBundle\Fetcher\PropertyPaths\PropertyPaths;
    use Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\QueryBuilderReverseGeneratorInterface;
    use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Component;
    use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\ComponentFactory;

    class EntityReverseQueryBuilderGenerator implements QueryBuilderReverseGeneratorInterface
    {
        public function __construct(
            private readonly ComponentFactory $componentFactory,
        )
        {
        }

        private Component $lastJoined;
        private string $lastAlias;


        public function generate(
            Component $component ,
            PropertyPaths $paths,
            QueryBuilder $queryBuilder,
        ): QueryBuilder
        {
            $this->lastJoined = $component;
            $this->lastAlias  = $component->getPropertyName();

            $queryBuilder = $this->addInverseSelect($queryBuilder);

            foreach ($paths as $path){
                $queryBuilder = $this->addInverseJoin($path, $queryBuilder);
                $queryBuilder = $this->addInverseCondition($path, $queryBuilder);

                if(!$this->lastJoined->isRoot()){
                    $this->lastAlias  = $this->lastJoined->getParentProperty();
                }

                $this->lastJoined = $path;


            }

            return $queryBuilder;
        }

        private function addInverseJoin(Component $component, QueryBuilder $queryBuilder): QueryBuilder
        {
            $parentProperty = $this->lastJoined->getParentProperty();

            return $queryBuilder->leftJoin($this->lastAlias . '.' . $parentProperty, $parentProperty);
        }

        private function addInverseSelect(QueryBuilder $queryBuilder): QueryBuilder
        {
            if($this->lastJoined->isRoot()){
                return $queryBuilder;
            }

            if(!$this->lastJoined->hasType(SmartFetchObjectManager::MANY_TO_MANY)){
                return $queryBuilder;
            }

            $parentProperty = $this->lastJoined->getParentProperty();

            return $queryBuilder->addSelect($parentProperty);
        }

        private function addInverseCondition(Component $component , QueryBuilder $queryBuilder): QueryBuilder
        {
            $parentProperty = $this->lastJoined->getParentProperty();

            foreach ($component->getPropertyCondition() as $condition){
                $queryBuilder = $queryBuilder
                    ->andWhere($parentProperty . '.' . $condition->property . $condition->operator . $condition->property)
                    ->setParameter($condition->property, $condition->value);
            }
            return $queryBuilder;
        }
    }