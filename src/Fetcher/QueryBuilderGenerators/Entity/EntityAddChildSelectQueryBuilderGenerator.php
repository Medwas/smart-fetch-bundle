<?php

namespace Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\Entity;

use Doctrine\ORM\QueryBuilder;
use Verclam\SmartFetchBundle\Fetcher\Configuration\Configuration;
use Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\Attributes\FilterBy;
use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\CompositeNode;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\Node;

/**
 * This class will addSelect to child in we want to maximize
 * this join number of the childs using the configuration
 */
class EntityAddChildSelectQueryBuilderGenerator
{
    private array $maxConfig = [
        SmartFetchObjectManager::ONE_TO_ONE         => 0,
        SmartFetchObjectManager::MANY_TO_MANY       => 0,
        SmartFetchObjectManager::MANY_TO_ONE        => 0,
        SmartFetchObjectManager::ONE_TO_MANY        => 0,
        SmartFetchObjectManager::SCALAR             => 0
    ];

    private array $addedMax = [
        SmartFetchObjectManager::ONE_TO_ONE         => 0,
        SmartFetchObjectManager::MANY_TO_MANY       => 0,
        SmartFetchObjectManager::MANY_TO_ONE        => 0,
        SmartFetchObjectManager::ONE_TO_MANY        => 0,
        SmartFetchObjectManager::SCALAR             => 0
    ];

    public function __construct(
        private readonly Configuration $configuration,
    )
    {
    }

    /**
     * @param Node $node
     * @param QueryBuilder $queryBuilder
     * @return QueryBuilder
     */
    public function generate(Node $node , QueryBuilder $queryBuilder): QueryBuilder
    {
        if(!($node instanceof CompositeNode))
        {
            return $queryBuilder;
        }

        $this->initMaxConfiguration();

        foreach ($node->getChildren() as $child){
            if($child->isScalar()){
                continue;
            }

            if($this->addedMax[$child->getRelationType()] >= $this->maxConfig[$child->getRelationType()]){
                continue;
            }

            ++$this->addedMax[$child->getRelationType()];

            $this->addSelect($child, $queryBuilder);
            $this->addJoin($child, $queryBuilder);
            $this->addCondition($child, $queryBuilder);
        }

        foreach ($node->getChildren() as $childNode){
            if(!$childNode->isFetchEager()){
                continue;
            }
            $this->addSelect($childNode, $queryBuilder);
            $this->addJoin($childNode, $queryBuilder);
            $this->addCondition($childNode, $queryBuilder);
        }

        $this->resetConfig();

        return $queryBuilder;
    }

    /**
     * @param Node $node
     * @param QueryBuilder $queryBuilder
     * @return void
     */
    private function addSelect(Node $node, QueryBuilder $queryBuilder): void
    {
        $queryBuilder->addSelect($node->getAlias());
    }

    /**
     * @param Node $node
     * @param QueryBuilder $queryBuilder
     * @return void
     */
    private function addJoin(Node $node, QueryBuilder $queryBuilder): void
    {
        $queryBuilder->leftJoin($node->getParentNode()->getAlias() . '.' . $node->getFieldName(),
            $node->getAlias());
    }

    /**
     * @param Node $node
     * @param QueryBuilder $queryBuilder
     * @return void
     */
    private function addCondition(Node $node, QueryBuilder $queryBuilder): void
    {
        /** @var FilterBy $condition */
        foreach ($node->getFieldConditionCollection() as $condition){
            $queryBuilder = $queryBuilder
                ->andWhere($node->getAlias() . '.' . $condition->fieldName . $condition->operator . $condition->fieldName)
                ->setParameter($condition->fieldName, $condition->value);
        }
    }

    /**
     * @return void
     */
    private function initMaxConfiguration(): void
    {
        $maxConfig = $this->configuration->getAll();

        foreach ($maxConfig as $key => $value){
            $this->maxConfig[$key] = $value;
        }

    }

    /**
     * @return void
     */
    private function resetConfig(): void
    {
        foreach ($this->addedMax as $key => $value){
            $this->addedMax[$key] = 0;
        }
    }

}