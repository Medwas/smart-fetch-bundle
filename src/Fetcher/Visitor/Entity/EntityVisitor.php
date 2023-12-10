<?php

    namespace Verclam\SmartFetchBundle\Fetcher\Visitor\Entity;

    use Doctrine\ORM\QueryBuilder;
    use Verclam\SmartFetchBundle\Attributes\SmartFetch;
    use Verclam\SmartFetchBundle\Attributes\SmartFetchEntity;
    use Verclam\SmartFetchBundle\Enum\FetchModeEnum;
    use Verclam\SmartFetchBundle\Fetcher\Configuration\Configuration;
    use Verclam\SmartFetchBundle\Fetcher\Hydrator\HydratorContainer;
    use Verclam\SmartFetchBundle\Fetcher\PropertyPaths\PropertyPaths;
    use Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\Entity\EntityQueryBuilderGenerator;
    use Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\QueryBuilderGeneratorsContainer;
    use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Component;
    use Verclam\SmartFetchBundle\Fetcher\Visitor\SmartFetchVisitorInterface;

    class EntityVisitor implements SmartFetchVisitorInterface
    {
        private PropertyPaths $paths;

        /**
         * @param Configuration                                $configuration
         * @param EntityQueryBuilderGenerator                  $queryBuilder
         */
        public function __construct(
            private readonly Configuration               $configuration,
            private readonly EntityQueryBuilderGenerator $queryBuilder,
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
            return $smartFetch instanceof SmartFetchEntity;
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

            if($component->getParent() && $component->isComposite()){
                $this->paths->add($component->getParent());
            }else{
                $this->paths->removeLast();
            }

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

            $component->setResult($result);
        }

        public function joinResult(Component $component): void
        {
            // nothing to do here because entities are object and every is done in the fetch method, so we find
            // the final result by default in the root component
        }
    }