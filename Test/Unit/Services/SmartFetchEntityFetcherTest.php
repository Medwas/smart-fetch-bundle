<?php

namespace Services;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Verclam\SmartFetchBundle\Attributes\SmartFetch;
use Verclam\SmartFetchBundle\Services\SmartFetchEntityFetcher;

class SmartFetchEntityFetcherTest extends TestCase
{
    private MockObject $objectManager;
    private MockObject $objectRepository;
    private MockObject $smartFetch;
    private MockObject $request;
    private MockObject $attributes;
    private MockObject $managerRegistry;
    private MockObject $queryBuilder;
    private MockObject $abstractQuery;
    private SmartFetchEntityFetcher $smartFetchEntityFetcher;

    protected function setUp(): void
    {
        $this->objectManager            = $this->createMock(ObjectManager::class);
        $this->abstractQuery            = $this->createMock(AbstractQuery::class);
        $this->queryBuilder             = $this->createMock(QueryBuilder::class);
        $this->objectRepository         = $this->getMockBuilder(ObjectRepository::class)
            ->onlyMethods(['find', 'findAll', 'findBy', 'findOneBy', 'getClassName'])
            ->addMethods(['createQueryBuilder'])
            ->getMock()
        ;

        $this->smartFetch               = $this->createMock(SmartFetch::class);
        $this->request                  = $this->createMock(Request::class);
        $this->attributes               = $this->createMock(ParameterBag::class);
        $this->request->attributes      = $this->attributes;
        $this->managerRegistry          = $this->createMock(ManagerRegistry::class);
        $this->smartFetchEntityFetcher  = new SmartFetchEntityFetcher($this->managerRegistry);
    }

    /**
     * @throws EntityNotFoundException
     * @throws NonUniqueResultException
     */
    public function testResolveShouldSuccess(): void
    {
        $entity         = new \stdClass();
        $className      = \stdClass::class;
        $queryName      = 'QueryName';
        $queryValue     = 'QueryValue';
        $rootAlias      = 's';

        $this->smartFetch->expects($this->once())->method('getQueryName')->willReturn($queryName);
        $this->smartFetch->expects($this->once())->method('getArgumentName')->willReturn(null);
        $this->smartFetch->expects($this->once())->method('getClass')->willReturn($className);
        $this->smartFetch->expects($this->once())->method('getEntityManager')->willReturn(null);
        $this->smartFetch->expects($this->once())->method('getJoinEntities')->willReturn([]);

        $this->attributes->expects($this->once())->method('get')->with($queryName)->willReturn($queryValue);

        $this->managerRegistry->expects($this->once())->method('getManagerForClass')->with($className)->willReturn($this->objectManager);

        $this->objectManager->expects($this->once())->method('getRepository')->with($className)->willReturn($this->objectRepository);

        $this->objectRepository->expects($this->once())->method('createQueryBuilder')->with($rootAlias)->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->once())->method('andWhere')
            ->with($rootAlias . '.' . $queryName . ' = :' . $queryName)
            ->willReturn($this->queryBuilder)
        ;

        $this->queryBuilder
            ->expects($this->once())
            ->method('setParameter')
            ->with($queryName, $queryValue)
            ->willReturn($this->queryBuilder)
        ;

        $this->queryBuilder
            ->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->abstractQuery)
        ;

        $this->abstractQuery->expects($this->once())->method('getOneOrNullResult')->willReturn($entity);

        $res = $this->smartFetchEntityFetcher->resolve($this->request, $this->smartFetch);

        $this->assertEquals([$entity], $res);
    }
}