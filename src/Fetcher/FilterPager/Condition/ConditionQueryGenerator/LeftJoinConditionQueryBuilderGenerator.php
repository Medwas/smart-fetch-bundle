<?php

namespace Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\ConditionQueryGenerator;

use Doctrine\ORM\QueryBuilder;
use Exception;
use ReflectionAttribute;
use ReflectionProperty;
use Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\Attributes\LeftJoin;
use Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\FieldCondition;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\Node;

class LeftJoinConditionQueryBuilderGenerator implements ConditionQueryBuilderGeneratorInterface
{
    private array $alreadyJoinedTable = [];

    /**
     * @param ReflectionAttribute[] $attributes
     * @return bool
     */
    private function getAttributes(ReflectionProperty $reflectionProperty): array
    {
        $reflectionAttributes = [];
        foreach ($reflectionProperty->getAttributes() as $attribute) {
            if ($attribute->getName() === LeftJoin::class) {
                $reflectionAttributes[] = $attribute->newInstance();
            }
        }
        return $reflectionAttributes;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function generateQuery(
        FieldCondition                  $fieldCondition,
        QueryBuilder                    $queryBuilder,
        Node                            $node,
    ): void {
        foreach ($fieldCondition->getLeftJoinConditions() as $condition) {
            $this->generateLeftJoin($queryBuilder, $condition, $node);
            $fieldCondition->addJoinedAlias($condition->getJoinedAliases());
        }
    }

    /**
     * @param FilterBy     $filterBy
     * @param string       $propertyName
     * @param array|string $filterByValues
     * @param QueryBuilder $queryBuilder
     *
     * @return void
     */
    private function generateLeftJoin(
        QueryBuilder    $queryBuilder,
        LeftJoin        $leftJoin,
        Node            $node,
    ): void {

        $joins = $leftJoin->joins;

        match ($leftJoin->withVersion) {
            true    => $this->generateLeftJoinWith($queryBuilder, $leftJoin, $node),
            false   => $this->generateLeftJoinSimple($queryBuilder, $leftJoin, $node),
        };
    }

    private function generateLeftJoinWith(
        QueryBuilder    $queryBuilder,
        LeftJoin        $leftJoin,
        Node            $node,
    ): void {
        $joins = $leftJoin->joins;

        foreach ($joins as $key => $join) {
            if (!$this->isAlreadyJoined($join)) {
                if ($key === 0) {
                    //TODO change the 'id' by searching for the primary key of the entity
                    $queryBuilder->leftJoin($leftJoin->className, $join, 'WITH', "$join.id = $alias.id");
                } else {
                    $newJoin = $alias . ucfirst($join);

                    if (!$this->isAlreadyJoined($newJoin)) {
                        $queryBuilder->leftJoin($alias . '.' . $join, $newJoin);
                    }

                    $join = $newJoin;
                }
            }

            $alias = $join;
            $this->addJoinedTable($join);
        }
    }

    private function generateLeftJoinSimple(
        QueryBuilder    $queryBuilder,
        LeftJoin        $leftJoin,
        Node            $node,
    ): void {
        $joins          = $leftJoin->joins;
        $alias          = $node->getAlias();
        $currentNode    = $node;

        foreach ($joins as $key => $joinFieldName) {

            $currentNode = $this->updateCurrentNode($currentNode, $joinFieldName);
            $joinFieldAlias = $this->getAlias($currentNode, $joinFieldName);

            if (!$leftJoin->isAlreadyJoined($joinFieldAlias)) {
                $queryBuilder->leftJoin($alias . '.' . $joinFieldName, $joinFieldAlias);
            }

            $alias = $joinFieldAlias;
            $leftJoin->addJoinedAliases($joinFieldName, $joinFieldAlias);
        }
    }

    private function getAlias(?Node $node, string $joinFieldName): string
    {
        return $node
            ? $node->getAlias()
            : $joinFieldName;
    }

    private function updateCurrentNode(?Node $node, string $joinFieldName): ?Node
    {
        return $node?->getChildByFieldName($joinFieldName);
    }

    private function isAlreadyJoined(string $relationName): bool
    {
        return key_exists($relationName, $this->alreadyJoinedTable);
    }

    private function addJoinedTable(string $relationName): void
    {
        $this->alreadyJoinedTable[$relationName] = true;
    }

    public function clear(): void
    {
        $this->alreadyJoinedTable = [];
    }

    /**
     * @inheritDoc
     */
    public function supports(FieldCondition $fieldCondition): bool
    {
        return $fieldCondition->hasLeftJoin();
    }
}
