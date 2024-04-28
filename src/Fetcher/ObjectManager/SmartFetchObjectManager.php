<?php

namespace Verclam\SmartFetchBundle\Fetcher\ObjectManager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Verclam\SmartFetchBundle\Attributes\SmartFetch;

class SmartFetchObjectManager
{

    public const ONE_TO_ONE = 1;
    public const ROOT = 999;
    public const MANY_TO_ONE = 4;
    public const ONE_TO_MANY = 2;
    public const MANY_TO_MANY = 8;
    public const SCALAR = 0;

    private ObjectManager $objectManager;

    public function __construct(
        private readonly ManagerRegistry $registry,
    ) {
    }

    public function createQueryBuilder(): QueryBuilder
    {
        return $this->objectManager->createQueryBuilder();
    }

    public function getObjectManager(): ?ObjectManager
    {
        return $this->objectManager;
    }

    public function initObjectManager(SmartFetch $smartFetch): ?ObjectManager
    {
        if (null === $name = $smartFetch->getEntityManager()) {
            $this->objectManager = $this->registry->getManagerForClass($smartFetch->getClass());
        }

        $this->objectManager = $this->registry->getManager($name);

        $this->disableFilters($smartFetch->disableFilters);
        $this->enableFilters($smartFetch->enableFilters);

        return $this->objectManager;
    }

    private function disableFilters(array $filters): void
    {
        if (!$this->objectManager) {
            return;
        }

        if (!count($filters)) {
            return;
        }

        if (!$this->objectManager instanceof EntityManagerInterface) {
            //TODO: should probably throw or log somthing to let the dev knows that he gave a filter that cannot be disabled
            return;
        }

        foreach ($filters as $filter) {
            $this->objectManager->getFilters()->disable($filter);
        }
    }

    private function enableFilters(array $filters): void
    {
        if (!$this->objectManager) {
            return;
        }

        if (!count($filters)) {
            return;
        }

        if (!$this->objectManager instanceof EntityManagerInterface) {
            //TODO: should probably throw or log somthing to let the dev knows that he gave a filter that cannot be disabled
            return;
        }

        foreach ($filters as $filter) {
            $this->objectManager->getFilters()->enable($filter);
        }
    }

    public function getClassMetadata(string $className): ClassMetadata
    {
        return $this->objectManager->getClassMetadata($className);
    }
}
