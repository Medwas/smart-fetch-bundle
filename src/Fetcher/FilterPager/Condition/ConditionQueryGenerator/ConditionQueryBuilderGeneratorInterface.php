<?php

namespace Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\ConditionQueryGenerator;

use Doctrine\ORM\QueryBuilder;
use Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\FieldCondition;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\Node;

interface ConditionQueryBuilderGeneratorInterface
{
    /**
     * @param FieldCondition $fieldCondition
     * @return bool
     */
    public function supports(FieldCondition $fieldCondition): bool;

    /**
     * @param FieldCondition $fieldCondition
     * @param QueryBuilder $queryBuilder
     * @param Node $node
     * @return void
     */
    public function generateQuery(
        FieldCondition                  $fieldCondition,
        QueryBuilder                    $queryBuilder,
        Node                            $node,
    ): void;

    public function clear(): void;
}
