<?php

    namespace Verclam\SmartFetchBundle\Services;

    use Doctrine\ORM\NonUniqueResultException;
    use Doctrine\Persistence\ObjectRepository;
    use Symfony\Component\HttpFoundation\Request;
    use Doctrine\Persistence\ManagerRegistry;
    use Doctrine\Persistence\ObjectManager;
    use Doctrine\ORM\EntityNotFoundException;
    use Doctrine\ORM\QueryBuilder;
    use Verclam\SmartFetchBundle\Attributes\SmartFetch;

    class SmartFetchEntityFetcher
    {

        private ObjectManager               $objectManager;
        private ?ObjectRepository           $repository = null;
        private ?string                     $entityName = null;
        private ?string                     $fullEntityName = null;
        
        public function __construct(
            private readonly ManagerRegistry $registry,
        )
        {
        }

        /**
         * @throws EntityNotFoundException
         * @throws NonUniqueResultException
         */
        public function resolve(Request $request, SmartFetch $attribute): iterable
        {
            $queryName              = $attribute->getQueryName();
            $queryValue             = $request->attributes->get($queryName);
            $argumentName           = $attribute->getArgumentName();
            $this->fullEntityName   = $attribute->getClass();

            if (\is_object($queryValue)) {
                return [];
            }

            if ($argumentName && \is_object($request->attributes->get($argumentName))) {
                return [];
            }

            if (!$this->fullEntityName) {
                return [];
            }

            if (!$this->objectManager = $this->getManager($attribute)) {
                return [];
            }

            if(empty($queryValue)) {
                throw new \LogicException(sprintf('Unable to guess how to get a Doctrine instance from the request information for parameter "%s".', $queryName));
            }

            $this->entityName  = $this->getEntityName();

            $this->repository   = $this
                ->objectManager
                ->getRepository($this->fullEntityName);

            $joinAliases        = $attribute->getJoinEntities();

            $entity             = $this->fetchEntity($queryValue, $queryName, $joinAliases);
            
            return [$entity];
        }

        /**
         * @throws NonUniqueResultException
         * @throws EntityNotFoundException
         */
        private function fetchEntity(
            string  $value,
            string  $valueName,
            array   $joinAliases
        ): ?object
        {
            $entityName  = $this->entityName;
            $rootAlias   = strtolower(substr($entityName, 0, 1));

            $queryBuilder = $this
                ->repository
                ->createQueryBuilder($rootAlias)
                ->andWhere($rootAlias . '.' . $valueName . ' = :' . $valueName)
                ->setParameter($valueName, $value);
            ;

            $queryBuilder       = $this->addLinkedEnities($rootAlias, $joinAliases, $queryBuilder);

            $entity             = $queryBuilder->getQuery()->getOneOrNullResult();

            if(!$entity instanceof $this->fullEntityName){
                throw EntityNotFoundException::fromClassNameAndIdentifier(
                    $this->entityName,
                    [
                        'id' => $value,
                    ],
                );
            }

            return $entity;
        }

        protected function addLinkedEnities(
            string          $rootAlias,
            array|string    $joinAliases,
            QueryBuilder    $queryBuilder
        ): QueryBuilder
        {

            if(is_string($joinAliases)){
                $joinAliases = [$joinAliases];
            }

            foreach ($joinAliases as $joinAlias) {

                $joinAlias = str_contains($joinAlias, '.') ? explode('.',$joinAlias) : $joinAlias;

                if(is_array($joinAlias)){
                    $queryBuilder = $this->addLinkedEnities($joinAlias[0], $joinAlias[1], $queryBuilder);
                    continue;
                }

                $queryBuilder->leftJoin($rootAlias . '.' . $joinAlias, $joinAlias);
                $queryBuilder->addSelect($joinAlias);
            }

            return $queryBuilder;
        }

        private function getEntityName(): string
        {
            if(!class_exists($this->fullEntityName)){
                throw new \LogicException(sprintf('The class "%s" is incorrect', $this->fullEntityName));
            }

            $entityNameParts    = explode('\\', $this->fullEntityName);

            return end($entityNameParts);
        }

        private function getManager(SmartFetch $configuration) : ?ObjectManager
        {
            if (null === $name = $configuration->getEntityManager()) {
                return $this->registry->getManagerForClass($this->fullEntityName);
            }

            return $this->registry->getManager($name);
        }

    }