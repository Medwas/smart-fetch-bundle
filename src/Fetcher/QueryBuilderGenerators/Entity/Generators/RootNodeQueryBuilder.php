<?php

namespace Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\Entity\Generators;

use Doctrine\ORM\QueryBuilder;
use Verclam\SmartFetchBundle\Fetcher\Condition\Attributes\Condition;
use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
use Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\NodeQueryBuilderGeneratorInterface;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\Node;

class RootNodeQueryBuilder implements NodeQueryBuilderGeneratorInterface
{

    public function __construct(
        private readonly SmartFetchObjectManager                    $objectManager,
    )
    {
    }

    public function generate(Node $node): QueryBuilder
    {
        $queryBuilder = $this->objectManager->createQueryBuilder()
            ->select($node->getAlias())
            ->from($node->getClassName(), $node->getAlias());

        $this->addCondition($node, $queryBuilder);
        return $queryBuilder;
    }

    public function support(Node $node): bool
    {
        return $node->isRoot();
    }

    /**
     * @param Node $node
     * @param QueryBuilder $queryBuilder
     * @return void
     */
    private function addCondition(Node $node, QueryBuilder $queryBuilder): void
    {
        /** @var Condition $condition */
        foreach ($node->getPropertyCondition() as $condition){
            $queryBuilder = $queryBuilder
                ->andWhere($node->getAlias() . '.' . $condition->property . $condition->operator . $condition->property)
                ->setParameter($condition->property, $condition->value);
        }
    }

}