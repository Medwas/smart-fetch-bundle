<?php

namespace Verclam\SmartFetchBundle\Fetcher\ResultsJoiner\Array;

use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
use Verclam\SmartFetchBundle\Fetcher\PropertyPaths\PropertyPaths;
use Verclam\SmartFetchBundle\Fetcher\ResultsJoiner\ResultsJoinerInterface;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Component;

class ResultsJoiner implements ResultsJoinerInterface
{
    private ?PropertyPaths $propertyPaths = null;

    public function __construct()
    {
        $this->propertyPaths = new PropertyPaths();
    }

    function joinResult(Component $component, array &$result = []): array
    {
        if($component->isRoot()){
            $result = $component->getResult();
        }

        if($component->isComposite()){
            /** @var Component $child */
            foreach ($component->getChildren() as $child){
                if($child->isScalar()){
                    continue;
                }
                
                if(!$component->isRoot()){
                    $this->propertyPaths->add($component);
                }

                $this->join($child, $this->propertyPaths, $result);
                $this->joinResult($child, $result);
            }

            $this->propertyPaths->removeLast();
        }

        return $result;
    }

    private function join(Component $component, PropertyPaths $paths, array &$result = []): void
    {
        match ($component->getParent()->isRoot()){
            true     => $this->joinRootResult($component, $result),
            false    => $this->prepareAndJoinChildResult($component, $paths, $result),
        };
    }

    private function joinRootResult(Component $childNode, array &$parentResults): void
    {
        $propertyName                   = $childNode->getPropertyName();
        $parentResults[$propertyName]   = $childNode->getResult();
    }

    private function prepareAndJoinChildResult(Component $childNode, PropertyPaths $paths, array &$parentResults): void
    {
        if($paths->count() === 0){
            $this->joinChildResult($childNode, $paths, $parentResults);
            return;
        }

        $clonedPaths = clone $paths;
        if($paths->has(0) && $paths->get(0)->getParent()->isRoot()){
            $clonedPaths->reverse();
        }

        foreach ($clonedPaths as $currentPath){
            if($currentPath->getParent()->isRoot()){
                $this->prepareAndJoinChildResult(
                    $childNode,
                    $clonedPaths->removeLast(),
                    $parentResults[$currentPath->getPropertyName()]
                );
                break;
            }

            foreach ($parentResults as $parentKey => $parentResult) {
                $this->prepareAndJoinChildResult(
                    $childNode,
                    $clonedPaths->removeLast(),
                    $parentResults[$parentKey][$currentPath->getPropertyName()]
                );
            }
        }
    }

    /**
     * @throws Exception
     */
    private function joinChildResult(Component $childNode, PropertyPaths $paths, array &$parentResults): void
    {
        $childResults   = $childNode->getResult();
        $parent         = $childNode->getParent();

        $parentIdentifier = $parent->getClassMetadata()->getIdentifier();

        if(count($parentIdentifier) > 1){
            throw new Exception('Composite keys are not supported');
        }

        $parentIdentifier   = $parentIdentifier[0];
        $identifierAlias    = $parent->getAlias() . '_' . $parentIdentifier;
        $childFieldName     = $childNode->getPropertyName();

        foreach ($childResults as $childKey => $childResult) {
            foreach ($parentResults as $parentKey => $parentResult) {
                if($parentResult[$parentIdentifier] === $childResult[$identifierAlias]){
                    unset($childResult[$identifierAlias]);

                    if($childNode->hasType(SmartFetchObjectManager::MANY_TO_ONE) ||
                        $childNode->hasType(SmartFetchObjectManager::MANY_TO_MANY)
                    ){
                        $parentResults[$parentKey][$childFieldName][] = $childResult;
                    }else{
                        $parentResults[$parentKey][$childFieldName] = $childResult;
                    }

                    unset($childResults[$childKey]);
                    continue 2;
                }
            }
        }

        $childNode->setResult($childResults);
    }
}