<?php

namespace Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\Array;

use Doctrine\ORM\QueryBuilder;
use Verclam\SmartFetchBundle\Fetcher\Condition\Attributes\Condition;
use Verclam\SmartFetchBundle\Fetcher\Configuration\Configuration;
use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Component;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Composite;

/**
 * This class will addSelect to child in we want to maximize
 * this join number of the childs using the configuration
 */
class ArrayAddChildSelectQueryBuilderGenerator
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
     * @param Component $component
     * @param QueryBuilder $queryBuilder
     * @return QueryBuilder
     */
    public function generate(Component $component , QueryBuilder $queryBuilder): QueryBuilder
    {
        if(!($component instanceof Composite))
        {
            return $queryBuilder;
        }

        $this->initMaxConfiguration();

        foreach ($component->getChildren() as $child){
            if($child->isInitialized()){
                continue;
            }

            if($this->addedMax[$child->getRelationType()] >= $this->maxConfig[$child->getRelationType()]){
                continue;
            }

            ++$this->addedMax[$child->getRelationType()];

            $this->addSelect($child, $queryBuilder);
            $this->addJoin($child, $queryBuilder);
            $this->addCondition($child, $queryBuilder);
            $child->setIsInitialized(true);
        }

        $this->resetConfig();

        return $queryBuilder;
    }

    /**
     * @param Component $component
     * @param QueryBuilder $queryBuilder
     * @return void
     */
    private function addSelect(Component $component, QueryBuilder $queryBuilder): void
    {
        $queryBuilder->addSelect($component->getAlias());
    }

    /**
     * @param Component $component
     * @param QueryBuilder $queryBuilder
     * @return void
     */
    private function addJoin(Component $component, QueryBuilder $queryBuilder): void
    {
        $queryBuilder->leftJoin($component->getParent()->getAlias() . '.' . $component->getPropertyName(),
            $component->getAlias());
    }

    /**
     * @param Component $component
     * @param QueryBuilder $queryBuilder
     * @return void
     */
    private function addCondition(Component $component, QueryBuilder $queryBuilder): void
    {
        /** @var Condition $condition */
        foreach ($component->getPropertyCondition() as $condition){
            $queryBuilder = $queryBuilder
                ->andWhere($component->getAlias() . '.' . $condition->property . $condition->operator . $condition->property)
                ->setParameter($condition->property, $condition->value);
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