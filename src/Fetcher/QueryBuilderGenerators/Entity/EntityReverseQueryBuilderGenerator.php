<?php

namespace Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\Entity;

use Doctrine\ORM\QueryBuilder;
use Verclam\SmartFetchBundle\Fetcher\History\HistoryPaths;
use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
use Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\QueryBuilderReverseGeneratorInterface;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Component;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\ComponentFactory;

/**
 * In order to optimise the queries, in many case we optimise it
 * by dividing the request to database, so sometimes we are deep in tree
 * in order to create the query builder we need to reverse the construction
 * of the queryBuilder using the HistoryPaths, for in the final adding the condition,
 * to fetch only the entities that corresponds to the condition, for example the {id}
 * of the root.
 */
class EntityReverseQueryBuilderGenerator implements QueryBuilderReverseGeneratorInterface
{
    public function __construct(
        private readonly ComponentFactory $componentFactory,
    )
    {
    }

    private Component $lastJoined;
    private string $lastAlias;

    /**
     * @param Component $component
     * @param HistoryPaths $paths
     * @param QueryBuilder $queryBuilder
     * @return QueryBuilder
     */
    public function generate(
        Component $component ,
        HistoryPaths $paths,
        QueryBuilder $queryBuilder,
    ): QueryBuilder
    {
        $this->lastJoined = $component;
        $this->lastAlias  = $component->getAlias();

        $queryBuilder = $this->addInverseSelect($queryBuilder);

        foreach ($paths as $path){
            $queryBuilder = $this->addInverseJoin($path, $queryBuilder);
            $queryBuilder = $this->addInverseCondition($path, $queryBuilder);

            if(!$this->lastJoined->isRoot()){
                $this->lastAlias  = $this->lastJoined->getParent()->getAlias();
            }

            $this->lastJoined = $path;


        }

        return $queryBuilder;
    }

    /**
     * @param Component $component
     * @param QueryBuilder $queryBuilder
     * @return QueryBuilder
     */
    private function addInverseJoin(Component $component, QueryBuilder $queryBuilder): QueryBuilder
    {
        $parentProperty = $this->lastJoined->getParentProperty();
        $parentAlias = $this->lastJoined->getParent()->getAlias();

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

        $parentAlias = $this->lastJoined->getParent()->getAlias();

        return $queryBuilder->addSelect($parentAlias);
    }

    /**
     * @param Component $component
     * @param QueryBuilder $queryBuilder
     * @return QueryBuilder
     */
    private function addInverseCondition(Component $component , QueryBuilder $queryBuilder): QueryBuilder
    {
        $parentAlias = $component->getAlias();

        foreach ($component->getPropertyCondition() as $condition){
            $queryBuilder = $queryBuilder
                ->andWhere($parentAlias . '.' . $condition->property . $condition->operator . $condition->property)
                ->setParameter($condition->property, $condition->value);
        }
        return $queryBuilder;
    }
}