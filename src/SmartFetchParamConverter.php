<?php

namespace Verclam\SmartFetchBundle;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Verclam\SmartFetchBundle\Attributes\SmartFetch;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;

class SmartFetchParamConverter implements ParamConverterInterface
{

    public const NAME = 'smart_fetcher_param_converter';
    private ObjectManager               $objectManager;
    private ?ServiceEntityRepository    $repository = null;
    private ?string                     $entityName = null;
    private ?string                     $fullEntityName = null;

    public function __construct(
        private readonly ManagerRegistry $registry,
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function apply(Request $request, ParamConverter $configuration)
    {
        $name       = $configuration->getName();
        $value      = $request->attributes->get($name);
        $this->objectManager = $this->getManager($configuration);

        if(empty($value)) {
            throw new \LogicException(sprintf('Unable to guess how to get a Doctrine instance from the request information for parameter "%s".', $name));
        }

        $this->generateEntityName($configuration);

        $this->repository   = $this
            ->objectManager
            ->getRepository($this->fullEntityName);

        $joinAliases        = $configuration->getJoinEntities();
        $argumentName       = $configuration->getArgumentName() ?? $name;

        $entity             = $this->fetchEntity($value, $name, $joinAliases);

        $request->attributes->set($argumentName, $entity);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function supports(ParamConverter $configuration)
    {
        if(!($configuration instanceof SmartFetch)){
            return false;
        }

        if (null === $configuration->getClass()) {
            return false;
        }


        $em = $this->getManager($configuration);
        if (null === $em) {
            return false;
        }

        return !$em->getMetadataFactory()->isTransient($configuration->getClass());
    }

    /**
     * @param string $value
     * @param string $valueName
     * @param array  $joinAliases
     *
     * @return object|null
     * @throws \Doctrine\ORM\EntityNotFoundException
     * @throws \Doctrine\ORM\NonUniqueResultException
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

    /**
     * @param string                     $rootAlias
     * @param array|string               $joinAliases
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function addLinkedEnities(
        string          $rootAlias,
        array|string    $joinAliases,
        QueryBuilder    $queryBuilder
    ): QueryBuilder
    {

        if(is_string($joinAliases)){
            $joinAliases = [$joinAliases];
        }

        foreach ($joinAliases as $key => $joinAlias) {

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

    /**
     * @param string                     $rootAlias
     * @param string                     $joinAliase
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function addSelectAndLeftJoin(
        string          $rootAlias,
        string          $joinAliase,
        QueryBuilder    $queryBuilder
    ): QueryBuilder
    {
        $queryBuilder->leftJoin($rootAlias . '.' . $joinAliase, $joinAliase);
        $queryBuilder->addSelect($joinAliase);
        return $queryBuilder;
    }

    private function generateEntityName(ParamConverter $configuration): void
    {
        $this->fullEntityName = $configuration->getClass();

        if(!class_exists($this->fullEntityName)){
            throw new \LogicException(sprintf('The class "%s" is incorrect', $this->fullEntityName));
        }

        $entityNameParts    = explode('\\', $this->fullEntityName);
        $this->entityName   = end($entityNameParts);
    }

    private function getManager(SmartFetch $configuration) : ?ObjectManager
    {
        if (null === $name = $configuration->getEntityManager()) {
            return $this->registry->getManagerForClass($configuration->getClass());
        }

        return $this->registry->getManager($name);
    }
}