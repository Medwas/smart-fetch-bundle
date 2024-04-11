<?php

namespace Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\Array;

use Doctrine\ORM\QueryBuilder;
use Exception;
use Verclam\SmartFetchBundle\Fetcher\Condition\Attributes\Condition;
use Verclam\SmartFetchBundle\Fetcher\History\HistoryPaths;
use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
use Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\QueryBuilderGeneratorInterface;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Component;

class ArrayQueryBuilderGenerator implements QueryBuilderGeneratorInterface
{
    private Component $lastJoined;

    public function __construct(
        private readonly SmartFetchObjectManager                    $objectManager,
        private readonly ArrayReverseQueryBuilderGenerator          $reverseQBGenerator,
        private readonly ArrayAddChildSelectQueryBuilderGenerator   $addChildSelectQueryBuilderGenerator,
    )
    {
    }

    /**
     * @param Component $component
     * @param HistoryPaths $paths
     * @return QueryBuilder
     * @throws Exception
     */
    public function generate(Component $component, HistoryPaths $paths): QueryBuilder
    {
        $queryBuilder = match($component->isRoot()) {
            true        => $this->buildRootQueryBuilder($component),
            false       => $this->buildComponentQueryBuilder($component, $paths)
        };

        return $this->addChildSelectQueryBuilderGenerator
            ->generate($component, $queryBuilder);
    }

    /**
     * @param Component $component
     * @return QueryBuilder
     */
    private function buildRootQueryBuilder(Component $component): QueryBuilder
    {
        $queryBuilder = $this->objectManager->createQueryBuilder()
            ->select($this->buildScalarSelect($component))
            ->from($component->getClassName(), $component->getAlias());

        $this->lastJoined = $component;

        $this->addCondition($component, $queryBuilder);
        return $queryBuilder;
    }

    /**
     * @param Component $component
     * @param HistoryPaths $paths
     * @return QueryBuilder
     * @throws Exception
     */
    private function buildComponentQueryBuilder(Component $component, HistoryPaths $paths): QueryBuilder
    {
        $parent = $component->getParent();

        $queryBuilder = $this->objectManager->createQueryBuilder()
            ->select($this->buildScalarSelect($component))
            ->from($parent->getClassName(), $parent->getAlias());

        $this->lastJoined = $parent;

        $this->addSelect($component, $queryBuilder);
        $this->addJoin($component, $queryBuilder);
        $this->addCondition($parent, $queryBuilder);

        return $this->reverseQBGenerator->generate($parent, $paths, $queryBuilder);
    }

    /**
     * @param Component $component
     * @return string
     */
    private function buildScalarSelect(Component $component): string
    {
        $alias = $component->getAlias();
        $selector = '';

        /** @var Component $child */
        foreach ($component->getChildren() as $child){
            if(!$child->isScalar()){
                continue;
            }

            if(strlen($selector) > 0){
                $selector .= ', ';
            }

            $selector .= $alias . '.' . $child->getPropertyName();
            $child->setHasBeenHydrated(true);
            $child->setIsInitialized(true);
        }

        return $selector;
    }

    /**
     * @param Component $component
     * @param QueryBuilder $queryBuilder
     * @return void
     * @throws Exception
     */
    private function addSelect(Component $component, QueryBuilder $queryBuilder): void
    {
        if($component->getParent()->isRoot() && !$component->getParent()->isCollection()){
            return;
        }

        $parent = $this->lastJoined;

        $identifierProperty = $parent->getClassMetadata()->getIdentifier();

        if(count($identifierProperty) > 1){
            throw new Exception(
                'Composite keys are not supported, Doctrine\'s best practice, says that it is better to avoid using it'
            );
        }

        $identifierProperty = $identifierProperty[0];

        $identifierField = $parent->getAlias() . '.' . $identifierProperty;
        $identifierAlias = $parent->getAlias() . '_' . $identifierProperty;

        $queryBuilder->addSelect("$identifierField as $identifierAlias");
    }

    /**
     * @param Component $component
     * @param QueryBuilder $queryBuilder
     * @return void
     */
    private function addJoin(Component $component, QueryBuilder $queryBuilder): void
    {
        $queryBuilder->leftJoin($this->lastJoined->getAlias() . '.' . $component->getPropertyName(),
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
}