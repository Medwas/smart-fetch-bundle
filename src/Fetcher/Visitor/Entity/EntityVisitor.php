<?php

    namespace Verclam\SmartFetchBundle\Fetcher\Visitor\Entity;

    use Doctrine\ORM\QueryBuilder;
    use Verclam\SmartFetchBundle\Fetcher\Configuration\Configuration;
    use Verclam\SmartFetchBundle\Fetcher\Hydrator\HydratorContainer;
    use Verclam\SmartFetchBundle\Fetcher\Hydrator\SmartFetchHydratorInterface;
    use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
    use Verclam\SmartFetchBundle\Fetcher\PropertyPaths\PropertyPaths;
    use Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\Entity\EntityQueryBuilderGenerator;
    use Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\QueryBuilderGeneratorsContainer;
    use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Component;
    use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Composite;
    use Verclam\SmartFetchBundle\Fetcher\Visitor\SmartFetchVisitorInterface;

    class EntityVisitor implements SmartFetchVisitorInterface
    {
        private PropertyPaths $paths;

        /**
         * @param Configuration                                $configuration
         * @param EntityQueryBuilderGenerator                  $queryBuilder
         * @param iterable<mixed, SmartFetchHydratorInterface> $hydratorContainer
         */
        public function __construct(
            private readonly Configuration               $configuration,
            private readonly EntityQueryBuilderGenerator $queryBuilder,
            private readonly iterable                    $hydratorContainer = [],
        )
        {
            $this->paths = new PropertyPaths();
        }

        public function start(Component $component): void
        {
            $component->handle($this);
        }

        public function support(Configuration $configuration): bool
        {
            return $configuration->hasFetchMode(Configuration::ENTITY_FETCH_MODE);
        }

        /**
         * @throws \Exception
         */
        public function generate(Component $component): void
        {
            //TODO: ADD MANAGEMENT OF THE MAX CONFIGURATION
            //TODO: IMPLEMENT THE OTHER HYDRATORS
            $queryBuilder = $this->generateQuery($component);

            $this->fetch($component, $queryBuilder);

            $this->refreshTree($component);

            $this->hydrate($component);

            if($component->isComposite()){
                $this->paths->add($component);
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
            $result = match ($component->getRelationType()){
                SmartFetchObjectManager::ONE_TO_ONE, SmartFetchObjectManager::MANY_TO_ONE => $queryBuilder->getQuery()->getOneOrNullResult(),
                SmartFetchObjectManager::MANY_TO_MANY, SmartFetchObjectManager::ONE_TO_MANY => $queryBuilder->getQuery()->getResult(),
                default => throw new \Exception('No fetch mode found'),
            };

            $component->setResult($result);

        }

        /**
         * @throws \Exception
         */
        private function refreshTree(Component $component): void
        {
            if(!($component instanceof Composite)){
                return;
            }

            $classMetaData = $component->getClassMetadata();
            foreach ($component->getChildren() as $child){
                if(!$child->isInitialized()){
                    continue;
                }
                if(!$child->isComposite()){
                    $child->setIsInitialized(true);
                    continue;
                }
                if($child->getResult()){
                    throw new \Exception('Unexpected result');
                }
                $propertyReflexion = $classMetaData->getReflectionProperty($child->getPropertyName());
                $childResult = $propertyReflexion->getValue($component->getResult());
                $child->setResult($childResult);
            }
        }

        /**
         * @throws \Exception
         */
        private function hydrate(Component $component): void
        {
            if($component->isRoot()){
                return;
            }

            foreach ($this->hydratorContainer as $hydrator) {
                if ($hydrator->support($component, $this->configuration)) {
                    $hydrator->hydrate($component);
                }
            }
        }

    }