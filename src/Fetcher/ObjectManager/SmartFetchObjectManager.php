<?php

    namespace Verclam\SmartFetchBundle\Fetcher\ObjectManager;

    use Doctrine\Persistence\ObjectManager;
    use Doctrine\Persistence\ManagerRegistry;
    use Doctrine\Persistence\Mapping\ClassMetadata;
    use Doctrine\ORM\QueryBuilder;
    use Verclam\SmartFetchBundle\Attributes\SmartFetch;
    use Verclam\SmartFetchBundle\Attributes\SmartFetchInterface;

    class SmartFetchObjectManager
    {

        public const ONE_TO_ONE = 1;
        public const MANY_TO_ONE = 4;
        public const ONE_TO_MANY = 2;
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

        public function initObjectManager(SmartFetchInterface $smartFetch): ?ObjectManager
        {
            if (null === $name = $smartFetch->getEntityManager()) {
                return $this->objectManager = $this->registry->getManagerForClass($smartFetch->getClass());
            }

            return $this->objectManager = $this->registry->getManager($name);
        }

        public function getClassMetadata(string $className): ClassMetadata
        {
            return $this->objectManager->getClassMetadata($className);
        }

    }