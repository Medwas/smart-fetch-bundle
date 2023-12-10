<?php

    namespace Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\Array;

    use Doctrine\ORM\QueryBuilder;
    use Verclam\SmartFetchBundle\Fetcher\History\HistoryPaths;
    use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
    use Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\QueryBuilderReverseGeneratorInterface;
    use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Component;
    use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\ComponentFactory;

    class ArrayReverseQueryBuilderGenerator implements QueryBuilderReverseGeneratorInterface
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
            HistoryPaths $paths,
            QueryBuilder $queryBuilder,
        ): QueryBuilder
        {
            $this->lastJoined = $component;
            $this->lastAlias  = $component->getAlias();

            $queryBuilder = $this->addInverseSelect($queryBuilder);

            foreach ($paths as $path){
                $queryBuilder = $this->addInverseJoin($path, $queryBuilder);
                $queryBuilder = $this->addInverseCondition($path, $queryBuilder);

                if(!$this->lastJoined->isRoot()){
                    $this->lastAlias  = $this->lastJoined->getParent()->getAlias();
                }

                $this->lastJoined = $path;


            }

            return $queryBuilder;
        }

        private function addInverseJoin(Component $component, QueryBuilder $queryBuilder): QueryBuilder
        {
            $parentProperty = $this->lastJoined->getParentProperty();
            $parentAlias = $this->lastJoined->getParent()->getAlias();

            return $queryBuilder->leftJoin($this->lastAlias . '.' . $parentProperty, $parentAlias);
        }

        private function addInverseSelect(QueryBuilder $queryBuilder): QueryBuilder
        {
            if($this->lastJoined->isRoot()){
                return $queryBuilder;
            }

            if(!$this->lastJoined->hasType(SmartFetchObjectManager::MANY_TO_MANY)){
                return $queryBuilder;
            }

            $parentAlias = $this->lastJoined->getParent()->getAlias();

            return $queryBuilder->addSelect($parentAlias);
        }

        private function addInverseCondition(Component $component , QueryBuilder $queryBuilder): QueryBuilder
        {
            $parentAlias = $component->getAlias();

            foreach ($component->getPropertyCondition() as $condition){
                $queryBuilder = $queryBuilder
                    ->andWhere($parentAlias . '.' . $condition->property . $condition->operator . $condition->property)
                    ->setParameter($condition->property, $condition->value);
            }
            return $queryBuilder;
        }

        /**
         * @throws \Exception
         */
        private function buildAlias(Component $component, QueryBuilder $queryBuilder): string
        {
           $parentProperty = $this->lastJoined->getParentProperty();
           $allAliases = $queryBuilder->getAllAliases();
           if(in_array($parentProperty, $allAliases)){
               return $parentProperty . '_a';
           }

           return $parentProperty;
        }
    }