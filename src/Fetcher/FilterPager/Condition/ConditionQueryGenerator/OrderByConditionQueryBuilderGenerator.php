<?php

namespace Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\ConditionQueryGenerator;

use Doctrine\ORM\QueryBuilder;
use Exception;
use Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\Attributes\OrderBy;
use Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\FieldCondition;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\Node;

class OrderByConditionQueryBuilderGenerator implements ConditionQueryBuilderGeneratorInterface
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
        foreach ($fieldCondition->getOrderByConditions() as $condition) {
            $value                  = $condition->value;
            [$alias, $fieldName]    = $this->getAlias($fieldCondition, $condition, $node);
            $queryBuilder->orderBy($alias . '.' . $fieldName, $value);
        }
    }

    /**
     * @param FieldCondition $fieldCondition
     * @param OrderBy $orderBy
     * @param Node $node
     * @return array<string>
     */
    private function getAlias(
        FieldCondition  $fieldCondition,
        OrderBy         $orderBy,
        Node            $node,
    ): array
    {
        if(!$orderBy->isJoined()){
            return [$node->getAlias() ,$orderBy->fieldName];
        }

        [
            $filterByAlias,
            $filterByFieldName
        ] = explode('.', $orderBy->fieldName);

        $fieldName = $fieldCondition->getJoinedAliasFromFieldName($filterByAlias);

        return [$fieldName ,$filterByFieldName];
    }

    public function clear(): void
    {
        // TODO: Implement clear() method.
    }


    /**
     * @inheritDoc
     */
    public function supports(FieldCondition $fieldCondition): bool
    {
        return $fieldCondition->hasOrderBy();
    }

}
