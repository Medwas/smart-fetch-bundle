<?php

namespace Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\Entity;

use Doctrine\ORM\QueryBuilder;
use Verclam\SmartFetchBundle\Fetcher\Condition\Attributes\Condition;
use Verclam\SmartFetchBundle\Fetcher\History\HistoryPaths;
use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
use Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\QueryBuilderGeneratorInterface;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Component;

class EntityFetchEagerQueryBuilderGenerator implements QueryBuilderGeneratorInterface
{
    private Component $lastJoined;

    public function __construct(
        private readonly SmartFetchObjectManager                    $objectManager,
        private readonly EntityReverseQueryBuilderGenerator         $reverseQBGenerator,
        private readonly EntityAddChildSelectQueryBuilderGenerator  $addChildSelectQueryBuilderGenerator,
    ) {
    }

    /**
     * @param Component $component
     * @param HistoryPaths $paths
     * @return QueryBuilder
     */
    public function generate(Component $component, HistoryPaths $paths): QueryBuilder
    {
        $newPaths = new HistoryPaths();
        foreach ($paths as $path) {
            $newPaths->add($path);
        }
        $newPaths->add($component->getParent());

        foreach ($component->getFetchEagerChildren() as $eagerChild) {
            $queryBuilder = $this->objectManager->createQueryBuilder()
                ->select($eagerChild->getAlias())
                ->from($eagerChild->getClassName(), $eagerChild->getAlias());
            $this->reverseQBGenerator->generateFetchEager($eagerChild, $newPaths, $queryBuilder);

            $this->lastJoined = $component;

            $this->addCondition($component, $queryBuilder);

            $t = $queryBuilder->getQuery()->getResult();
        }

        return $queryBuilder;
    }

    /**
     * @param Component $component
     * @param QueryBuilder $queryBuilder
     * @return void
     */
    private function addCondition(Component $component, QueryBuilder $queryBuilder): void
    {
        /** @var Condition $condition */
        foreach ($component->getPropertyCondition() as $condition) {
            $queryBuilder = $queryBuilder
                ->andWhere($component->getAlias() . '.' . $condition->property . $condition->operator . $condition->property)
                ->setParameter($condition->property, $condition->value);
        }
    }
}
