<?php

    namespace Verclam\SmartFetchBundle\Fetcher\ObjectManager;

    use Doctrine\Persistence\ObjectManager;
    use Doctrine\Persistence\ManagerRegistry;
    use Doctrine\Persistence\Mapping\ClassMetadata;
    use Doctrine\ORM\QueryBuilder;
    use Verclam\SmartFetchBundle\Attributes\SmartFetch;

    class SmartFetchObjectManager
    {

        public const ONE_TO_ONE = 1;
        public const ONE_TO_MANY = 4;
        public const MANY_TO_ONE = 2;
        public const MANY_TO_MANY = 8;
        public const SCALAR = 'scalar';

        private ObjectManager $objectManager;

        public function __construct(
            private readonly ManagerRegistry $registry,
        )
        {
        }

        public function createQueryBuilder(): QueryBuilder
        {
            return $this->objectManager->createQueryBuilder();
        }

        private function getObjectManager() : ?ObjectManager
        {
            return $this->objectManager;
        }

        public function initObjectManager(SmartFetch $configuration): ?ObjectManager
        {
            if (null === $name = $configuration->getEntityManager()) {
                return $this->objectManager = $this->registry->getManagerForClass($configuration->getClass());
            }

            return $this->objectManager = $this->registry->getManager($name);
        }

        public function getClassMetadata(string $className): ClassMetadata
        {
            return $this->objectManager->getClassMetadata($className);
        }

    }