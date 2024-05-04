<?php

namespace Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\Array\Generators;

use Doctrine\ORM\QueryBuilder;
use Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\Attributes\FilterBy;
use Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\ConditionQueryGenerator\ConditionQueryBuilderGeneratorInterface;
use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
use Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\NodeQueryBuilderGeneratorInterface;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\Node;

class RootNodeQueryBuilder implements NodeQueryBuilderGeneratorInterface
{

    /**
     * @param SmartFetchObjectManager $objectManager
     * @param iterable<ConditionQueryBuilderGeneratorInterface> $conditionQueryGenerators
     */
    public function __construct(
        private readonly SmartFetchObjectManager    $objectManager,
        private iterable                            $conditionQueryGenerators,
    )
    {
    }
    
    /**
     * @param Node $node
     * @return QueryBuilder
     */
    public function generate(Node $node): QueryBuilder
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
        /** @var FilterBy $condition */
        foreach ($node->getFieldConditionCollection() as $fieldConditions){
            foreach ($this->conditionQueryGenerators as $conditionQueryGenerator){
                if($conditionQueryGenerator->supports($fieldConditions)){
                    $conditionQueryGenerator->generateQuery($fieldConditions, $queryBuilder, $node);
                    break;
                }
            }
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