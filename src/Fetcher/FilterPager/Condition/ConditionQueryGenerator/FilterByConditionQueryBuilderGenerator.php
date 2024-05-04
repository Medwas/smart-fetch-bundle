<?php

namespace Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\ConditionQueryGenerator;

use Doctrine\ORM\QueryBuilder;
use Exception;
use Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\Attributes\FilterBy;
use Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\FieldCondition;
use Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\Utils\DateUtils;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\Node;

class FilterByConditionQueryBuilderGenerator implements ConditionQueryBuilderGeneratorInterface
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

        foreach ($fieldCondition->getFilterByConditions() as $condition){
            [$alias, $fieldName] = $this->getAlias($fieldCondition, $condition, $node);

            match ($condition->operator) {
                FilterBy::EQUAL, FilterBy::NOT_EQUAL, FilterBy::GREATER_THAN_OR_EQUAL, FilterBy::GREATER_THAN,
                FilterBy::LESS_THAN_OR_EQUAL, FilterBy::LESS_THAN,
                FilterBy::IS_NULL, FilterBy::IN                 =>
                $this->generateStandard($condition, $queryBuilder, $node, $alias, $fieldName),
                FilterBy::BETWEEN                               =>
                $this->generateBetween($condition, $queryBuilder, $node, $alias, $fieldName),
                FilterBy::CUSTOM_CONDITION                      =>
                $this->generateCalculated($condition, $queryBuilder, $node),
                FilterBy::LIKE                                  =>
                $this->generateLike($condition, $queryBuilder, $node, $alias, $fieldName),
                default => throw new Exception('Not supported filter by type')
            };
        }
    }

    /**
     * @param FieldCondition $fieldCondition
     * @param FilterBy $filterBy
     * @param Node $node
     * @return array<string>
     */
    private function getAlias(
        FieldCondition  $fieldCondition,
        FilterBy        $filterBy,
        Node            $node,
    ): array
    {
        if(!$filterBy->isJoined()){
            return [$node->getAlias() ,$filterBy->fieldName];
        }

        [
            $filterByAlias,
            $filterByFieldName
        ] = explode('.', $filterBy->fieldName);

        $fieldName = $fieldCondition->getJoinedAliasFromFieldName($filterByAlias);

        return [$fieldName ,$filterByFieldName];
    }

    /**
     * @param FilterBy $filterBy
     * @param QueryBuilder $queryBuilder
     * @param Node $node
     * @param string $alias
     * @return void
     */
    private function generateStandard(
        FilterBy        $filterBy,
        QueryBuilder    $queryBuilder,
        Node            $node,
        string          $alias,
        string          $fieldName,
    ): void {
        $conditionLinker = $filterBy->conditionLinker->value;
        $fieldValue = $filterBy->value;


        $queryBuilder->$conditionLinker(
            $alias . '.' . $fieldName . $filterBy->negation . $filterBy->operator
            . $filterBy->prefix . $fieldName . $filterBy->suffix
        )
            ->setParameter($fieldName, $fieldValue);
    }

    /**
     * @param FilterBy $filterBy
     * @param QueryBuilder $queryBuilder
     * @param Node $node
     * @param string $alias
     * @return void
     */
    private function generateLike(
        FilterBy        $filterBy,
        QueryBuilder    $queryBuilder,
        Node            $node,
        string          $alias,
        string          $fieldName,
    ): void {
        $conditionLinker = $filterBy->conditionLinker->value;
//        $fieldName = $filterBy->fieldName;
        $fieldValue = $filterBy->value;

        $queryBuilder->$conditionLinker(
            $alias . '.' . $fieldName . $filterBy->negation . $filterBy->operator . $fieldName
        )
            ->setParameter($fieldName, $filterBy->prefix .  $fieldValue . $filterBy->suffix);
    }

    /**
     * @param FilterBy $filterBy
     * @param QueryBuilder $queryBuilder
     * @param Node $node
     * @param string $alias
     * @return void
     * @throws Exception
     */
    private function generateBetween(
        FilterBy        $filterBy,
        QueryBuilder    $queryBuilder,
        Node            $node,
        string          $alias
    ): void {
        try {
            $filterByValues = json_decode($filterBy->value, true);
        } catch (Exception $exception) {
            throw new Exception('Filter by between must be a json string');
        }

        if ($filterBy->dataTypes === FilterBy::DATA_TYPES_DATE) {
            $filterByValues[0] = DateUtils::getDate($filterByValues[0]);
            $filterByValues[1] = DateUtils::getDate($filterByValues[1]);
        }

        $fieldName = $filterBy->fieldName;

        $conditionLinker = $filterBy->conditionLinker->value;

        $queryBuilder->$conditionLinker(
            $alias . $filterBy->fieldName . $filterBy->negation . $filterBy->operator
            . $filterBy->prefix . $fieldName . 'Min' . $filterBy->suffix . $fieldName . 'Max'
        )
            ->setParameter($fieldName . 'Min', $filterByValues[0])
            ->setParameter($fieldName . 'Max', $filterByValues[1])
        ;
    }

    /**
     * @param FilterBy $filterBy
     * @param QueryBuilder $queryBuilder
     * @param Node $node
     * @return void
     */
    private function generateCalculated(
        FilterBy        $filterBy,
        QueryBuilder    $queryBuilder,
        Node            $node,
    ): void {
        $conditionLinker = $filterBy->conditionLinker->value;
        $fieldName = $filterBy->fieldName;
        $fieldValue = $filterBy->value;

        $queryBuilder->$conditionLinker($fieldName . ' ' . $filterBy->operator . $fieldValue);
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
        return $fieldCondition->hasFilterBy();
    }
}
