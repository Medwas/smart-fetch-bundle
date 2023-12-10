<?php

    namespace Verclam\SmartFetchBundle\Fetcher\Visitor\Array;

    use Doctrine\ORM\QueryBuilder;
    use Verclam\SmartFetchBundle\Attributes\SmartFetch;
    use Verclam\SmartFetchBundle\Attributes\SmartFetchArray;
    use Verclam\SmartFetchBundle\Enum\FetchModeEnum;
    use Verclam\SmartFetchBundle\Fetcher\Configuration\Configuration;
    use Verclam\SmartFetchBundle\Fetcher\Hydrator\HydratorContainer;
    use Verclam\SmartFetchBundle\Fetcher\PropertyPaths\PropertyPaths;
    use Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\Array\ArrayQueryBuilderGenerator;
    use Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\QueryBuilderGeneratorsContainer;
    use Verclam\SmartFetchBundle\Fetcher\ResultsJoiner\Array\ResultsJoiner;
    use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Component;
    use Verclam\SmartFetchBundle\Fetcher\Visitor\SmartFetchVisitorInterface;

    class ArrayVisitor implements SmartFetchVisitorInterface
    {
        private PropertyPaths $paths;

        /**
         * @param Configuration $configuration
         * @param ArrayQueryBuilderGenerator $queryBuilder
         */
        public function __construct(
            private readonly Configuration                  $configuration,
            private readonly ArrayQueryBuilderGenerator     $queryBuilder,
            private readonly ResultsJoiner                  $resultsJoiner,
        )
        {
            $this->paths = new PropertyPaths();
        }

        public function start(Component $component): void
        {
            $component->handle($this);
        }

        public function support(SmartFetch $smartFetch): bool
        {
            return $smartFetch instanceof SmartFetchArray;
        }

        /**
         * @throws \Exception
         */
        public function generate(Component $component): void
        {
            //TODO: ADD MANAGEMENT OF THE MAX CONFIGURATION
            $queryBuilder = $this->generateQuery($component);

            //TODO: Must manage one_to_one inverse side which automatically eager fetched
            //https://github.com/doctrine/orm/issues/4389
            //https://github.com/doctrine/orm/issues/3778
            //https://github.com/doctrine/orm/issues/4389
            //vendor/doctrine/orm/lib/Doctrine/ORM/UnitOfWork.php:2968
            $this->fetch($component, $queryBuilder);

            if($component->getParent() && $this->isRealComposite($component)) {
                $this->paths->add($component->getParent());
            }
        }

        private function isRealComposite(Component $component): bool
        {
            if(!$component->isComposite()){
                return false;
            }

            foreach ($component->getChildren() as $child){
                if(!$child->isScalar()){
                    return true;
                }
            }

            return false;
        }

        public function addPath(Component $component): void
        {
            $this->paths->add($component);
        }

        private function generateQuery(Component $component): QueryBuilder
        {
            return $this->queryBuilder->generate($component , $this->paths);
        }

        /**
         * @throws \Exception
         */
        private function fetch(Component $component, QueryBuilder $queryBuilder): void
        {
            $result = match ($component->isRoot()){
                true        => $queryBuilder->getQuery()->getOneOrNullResult(),
                false       => $queryBuilder->getQuery()->getResult(),
            };

            //in case when we have a single result and every field is null
            //that means no resul so we do it manually to an empty array
            if(count($result) === 1){
                foreach ($result[0] as $property){
                    if(!is_null($property)){
                        break;
                    }
                }
                $result = [];
            }

            $component->setResult($result);
        }

        public function joinResult(Component $component): void
        {
            $result = $this->resultsJoiner->joinResult($component);
            $component->setResult($result);
        }
    }