<?php

namespace Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\Array;

use Doctrine\ORM\QueryBuilder;
use Verclam\SmartFetchBundle\Fetcher\History\HistoryPaths;
use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
use Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\QueryBuilderReverseGeneratorInterface;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\Node;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\NodeFactory;

/**
 * In order to optimise the queries, in many case we optimise it
 * by dividing the request to database, so sometimes we are deep in tree
 * in order to create the query builder we need to reverse the construction
 * of the queryBuilder using the HistoryPaths, for in the final adding the condition,
 * to fetch only the entities that corresponds to the condition, for example the {id}
 * of the root.
 */
//TODO: to delete, we let it just in order to be sure that we will not need it anymore
class ArrayReverseQueryBuilderGenerator implements QueryBuilderReverseGeneratorInterface
{
    public function __construct(
        private readonly NodeFactory $nodeFactory,
    )
    {
    }

    private Node $lastJoined;
    private string $lastAlias;


    /**
     * @param Node $node
     * @param HistoryPaths $paths
     * @param QueryBuilder $queryBuilder
     * @return QueryBuilder
     */
    public function generate(
        Node $node ,
        HistoryPaths $paths,
        QueryBuilder $queryBuilder,
    ): QueryBuilder
    {
        $this->lastJoined = $node;
        $this->lastAlias  = $node->getAlias();

        $queryBuilder = $this->addInverseSelect($queryBuilder);

        foreach ($paths as $path){
            $queryBuilder = $this->addInverseJoin($path, $queryBuilder);
            $queryBuilder = $this->addInverseCondition($path, $queryBuilder);

            if(!$this->lastJoined->isRoot()){
                $this->lastAlias  = $this->lastJoined->getParentNode()->getAlias();
            }

            $this->lastJoined = $path;


        }

        return $queryBuilder;
    }

    /**
     * @param Node $node
     * @param QueryBuilder $queryBuilder
     * @return QueryBuilder
     */
    private function addInverseJoin(Node $node, QueryBuilder $queryBuilder): QueryBuilder
    {
        $parentProperty = $this->lastJoined->getParentProperty();
        $parentAlias = $this->lastJoined->getParentNode()->getAlias();

        return $queryBuilder->leftJoin($this->lastAlias . '.' . $parentProperty, $parentAlias);
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return QueryBuilder
     */
    private function addInverseSelect(QueryBuilder $queryBuilder): QueryBuilder
    {
        if($this->lastJoined->isRoot()){
            return $queryBuilder;
        }

        if(!$this->lastJoined->hasType(SmartFetchObjectManager::MANY_TO_MANY)){
            return $queryBuilder;
        }

        $parentAlias = $this->lastJoined->getParentNode()->getAlias();

        return $queryBuilder->addSelect($parentAlias);
    }

    /**
     * @param Node $node
     * @param QueryBuilder $queryBuilder
     * @return QueryBuilder
     */
    private function addInverseCondition(Node $node , QueryBuilder $queryBuilder): QueryBuilder
    {
        $parentAlias = $node->getAlias();

        foreach ($node->getPropertyCondition() as $condition){
            $queryBuilder = $queryBuilder
                ->andWhere($parentAlias . '.' . $condition->property . $condition->operator . $condition->property)
                ->setParameter($condition->property, $condition->value);
        }
        return $queryBuilder;
    }
}