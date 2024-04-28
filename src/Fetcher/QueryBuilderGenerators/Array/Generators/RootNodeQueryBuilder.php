<?php

namespace Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\Array\Generators;

use Doctrine\ORM\QueryBuilder;
use Verclam\SmartFetchBundle\Fetcher\Condition\Attributes\Condition;
use Verclam\SmartFetchBundle\Fetcher\History\HistoryPaths;
use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
use Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\NodeQueryBuilderGeneratorInterface;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\Node;

class RootNodeQueryBuilder implements NodeQueryBuilderGeneratorInterface
{

    public function __construct(
        private readonly SmartFetchObjectManager    $objectManager,
    )
    {
    }
    
    /**
     * @param Node $node
     * @return QueryBuilder
     */
    public function generate(Node $node, HistoryPaths $paths): QueryBuilder
    {
        $queryBuilder = $this->objectManager->createQueryBuilder()
            ->select($this->buildScalarSelect($node))
            ->from($node->getClassName(), $node->getAlias());

        $this->addCondition($node, $queryBuilder);
        return $queryBuilder;
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

    private function buildScalarSelect(Node $node): string
    {
        $alias = $node->getAlias();
        $selector = '';

        /** @var Node $child */
        foreach ($node->getChildren() as $child){
            if(!$child->isScalar()){
                continue;
            }

            if(strlen($selector) > 0){
                $selector .= ', ';
            }

            $selector .= $alias . '.' . $child->getFieldName();
        }

        return $selector;
    }

    public function support(Node $node): bool
    {
        return $node->isRoot();
    }
}