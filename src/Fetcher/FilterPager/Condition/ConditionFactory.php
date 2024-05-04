<?php

namespace Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Exception;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionProperty;
use Verclam\SmartFetchBundle\Attributes\SmartFetch;
use Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\Attributes\FilterBy;
use Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\Attributes\LeftJoin;
use Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\Attributes\OrderBy;
use Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\Attributes\Pager;
use Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\Attributes\SmartFetchConditionInterface;
use Verclam\SmartFetchBundle\Fetcher\FilterPager\DTO\AbstractFilterPagerDTO;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\Node;

class ConditionFactory
{
    /**
     * @throws Exception
     */
    public function generate(
        ClassMetadata   $classMetadata,
        SmartFetch      $smartFetch,
        Node            $node,
    ): void
    {
        $this->generateRootFilterBy($classMetadata, $smartFetch, $node);

        if(null === $filterPager = $smartFetch->getFilterPager()){
            return;
        }

        $properties = $this->getProperties($filterPager);

        foreach ($properties as $property){
            if (!$property->isInitialized($filterPager)) {
                continue;
            }
            $fieldCondition = new FieldCondition();

            foreach ($this->getAttributes($property, $filterPager) as $attribute) {
                if(!($attribute instanceof SmartFetchConditionInterface)){
                    continue;
                }

                $condition = match (true) {
                    $attribute instanceof LeftJoin => $this->createLeftJoinCondition($attribute, $fieldCondition),
                    $attribute instanceof FilterBy => $this->createFilterByCondition($attribute, $property, $filterPager, $fieldCondition),
                    $attribute instanceof OrderBy => $this->createOrderByCondition($attribute, $property, $filterPager),
                    $attribute instanceof Pager => $this->createPagerCondition($attribute, $property, $filterPager),
                    default => throw new Exception('Unknown attribute')
                };

                $fieldCondition->add($condition);
            }

            if(!$fieldCondition->hasConditions()){
                continue;
            }

            $node->addFieldCondition($fieldCondition);
        }

    }

    /**
     * @param AbstractFilterPagerDTO $filterDTO
     * @return ReflectionProperty[]
     */
    private function getProperties(AbstractFilterPagerDTO $filterDTO): array
    {
        $reflectionClass = new ReflectionClass($filterDTO);
        $properties = $reflectionClass->getProperties(ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_PUBLIC);

        if ($parentReflexionClass = $reflectionClass->getParentClass()) {
            $parentProperties = $parentReflexionClass->getProperties();
            $properties = array_merge($properties, $parentProperties);
        }

        return $properties;
    }

    private function createLeftJoinCondition(
        LeftJoin        $leftJoin,
        FieldCondition  $fieldCondition
    ): LeftJoin
    {
        $fieldCondition->setJoined(true);
        return $leftJoin;
    }

    private function createFilterByCondition(
        FilterBy                $filterBy,
        ReflectionProperty      $reflectionProperty,
        AbstractFilterPagerDTO  $pagerFilter,
        FieldCondition          $fieldCondition,
    ): FilterBy
    {
        $value = $reflectionProperty->getValue($pagerFilter);
        $filterBy->value = $value;
        return $filterBy;
    }

    private function createOrderByCondition(
        OrderBy $orderBy,
        ReflectionProperty $reflectionProperty,
        AbstractFilterPagerDTO $pagerFilter
    ): OrderBy
    {
        $value = $reflectionProperty->getValue($pagerFilter);
        $orderBy->value = $value;
        return $orderBy;
    }

    private function createPagerCondition(
        Pager $pager,
        ReflectionProperty $reflectionProperty,
        AbstractFilterPagerDTO $pagerFilter
    ): Pager
    {
        $pager->page = $pagerFilter->getPage();
        $pager->rows = $pagerFilter->getRows();
        return $pager;
    }

    /**
     * @param ReflectionProperty $reflectionProperty
     * @return ReflectionAttribute[] $attributes
     */
    private function getAttributes(ReflectionProperty $reflectionProperty): array
    {
        $reflectionAttributes = [];
        foreach ($reflectionProperty->getAttributes() as $attribute) {
            $reflectionAttributes[] = $attribute->newInstance();
        }

        return $reflectionAttributes;
    }

    private function generateRootFilterBy(
        ClassMetadata   $classMetadata,
        SmartFetch      $smartFetch,
        Node            $node,
    ): void
    {
        if($smartFetch->isCollection()){
           return;
        }

        $condition = new FilterBy(
            fieldName: $classMetadata->getIdentifier()[0],
            operator : FilterBy::EQUAL,
            options  : [],
            value    : $smartFetch->getQueryValue(),
        );

        $fieldCondition = new FieldCondition();
        $fieldCondition->add($condition);
        $node->addFieldCondition($fieldCondition);
    }

}