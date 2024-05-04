<?php

namespace Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\ConditionQueryGenerator;

use Doctrine\ORM\QueryBuilder;
use Exception;
use Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\FieldCondition;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\Node;

class PagerConditionQueryBuilderGenerator implements ConditionQueryBuilderGeneratorInterface
{

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function generateQuery(
        FieldCondition                  $fieldCondition,
        QueryBuilder                    $queryBuilder,
        Node                            $node,
    ): void {

        foreach ($fieldCondition->getPagerConditions() as $condition){
            try {
                $rows = $condition->rows;
                $page = $condition->page;

                $queryBuilder
                    ->setFirstResult($rows * $page)
                    ->setMaxResults($rows);
            } catch (Exception $e) {
                throw new Exception('Pager query generator error');
            }
        }
    }

    public function clear(): void
    {
        // TODO: Implement clear() method.
    }


    /**
     * @inheritDoc
     */
    public function supports(FieldCondition $fieldCondition,): bool
    {
        return $fieldCondition->hasPager();
    }
}
