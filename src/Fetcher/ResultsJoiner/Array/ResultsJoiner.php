<?php

namespace Verclam\SmartFetchBundle\Fetcher\ResultsJoiner\Array;

use Exception;
use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
use Verclam\SmartFetchBundle\Fetcher\PropertyPaths\PropertyPaths;
use Verclam\SmartFetchBundle\Fetcher\ResultsJoiner\ResultsJoinerInterface;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Component;

class ResultsJoiner implements ResultsJoinerInterface
{
    private ?PropertyPaths $history = null;

    public function __construct()
    {
        $this->history = new PropertyPaths();
    }

    /**
     * This function will be first called with the root node
     * and it will be called recursively with every non scalar child node,
     * the objectives of this function is to join the result of every child node
     * with the result of the root node.
     * @throws Exception
     */
    public function joinResult(Component $component, array &$result = []): array
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
                    $this->history->add($component);
                }

                $this->join($child, $this->history, $result);
                $this->joinResult($child, $result);
            }

            $this->history->removeLast();
        }

        return $result;
    }

    /**
     * @throws Exception
     */
    private function join(Component $component, PropertyPaths $paths, array &$result = []): void
    {
        match ($component->getParent()->isRoot()){
            true     => $this->joinRootResult($component, $result),
            false    => $this->prepareAndJoinChildResult($component, $paths, $result),
        };
    }

    /**
     * Join the root result with the child result
     * @param Component $childNode
     * @param array $parentResults
     * @return void
     */
    private function joinRootResult(Component $childNode, array &$parentResults): void
    {
        $propertyName                   = $childNode->getPropertyName();
        $parentResults[$propertyName]   = $childNode->getResult();
    }

    /**
     * This function will help us go as deeply as needed in the parent result
     * using the property paths as a history to know the path needed to reach the right place
     * where we need to join the child result, in the final the parent result of the root node will be updated
     * @param Component $childNode
     * @param PropertyPaths $history
     * @param array $parentResults
     * @return void
     * @throws Exception
     */
    private function prepareAndJoinChildResult(Component $childNode, PropertyPaths $history, array &$parentResults): void
    {
        //if there is no history, that means we are in a direct child of the root node
        //so it will be easy to join the child result with the parent result
        if($history->count() === 0){
            $this->joinChildResult($childNode, $history, $parentResults);
            return;
        }

        //we need to clone the history to avoid losing the original version history
        $clonedHistory = clone $history;
        //we need to reverse the history because the foreach in the history start from the end
        //so we check that we have 0 key and that this key's parent that is root
        //this condition help us reverse the histroy only once when we are digging
        //for a child
        if($history->has(0) && $history->get(0)->getParent()->isRoot()){
            $clonedHistory->reverse();
        }

        foreach ($clonedHistory as $currentHistory){
            if($currentHistory->getParent()->isRoot()){
                $this->prepareAndJoinChildResult(
                    $childNode,
                    $clonedHistory->removeLast(),
                    $parentResults[$currentHistory->getPropertyName()]
                );
                break;
            }

            foreach ($parentResults as $parentKey => $parentResult) {
                $this->prepareAndJoinChildResult(
                    $childNode,
                    $clonedHistory->removeLast(),
                    $parentResults[$parentKey][$currentHistory->getPropertyName()]
                );
            }
        }
    }

    /**
     * Join the parent result with the child result based on the identifier
     * that has been added in every child result
     * @throws Exception
     */
    private function joinChildResult(Component $childNode, PropertyPaths $paths, array &$parentResults): void
    {
        //if the parent result is empty, we don't need to join anything
        if(count($parentResults) === 0){
            return;
        }

        $childResults   = $childNode->getResult();
        $parent         = $childNode->getParent();

        $parentIdentifier = $parent->getClassMetadata()->getIdentifier();

        if(count($parentIdentifier) > 1){
            throw new Exception('Composite keys are not supported, Doctrine\'s best practice, says that it is better to avoid using it');
        }

        $parentIdentifier   = $parentIdentifier[0];
        $identifierAlias    = $parent->getAlias() . '_' . $parentIdentifier;
        $childFieldName     = $childNode->getPropertyName();

        //retrieve only the identifier column from the parent result to avoid make a double loop on the parent result
        $parentsIdentifierColumn = array_column($parentResults, $parentIdentifier);

        foreach ($childResults as $childKey => $childResult){
            //retrieve the key of the parent result that has the same identifier as the child result
            $intersectParentsKey = array_search($childResult[$identifierAlias], $parentsIdentifierColumn, true);

            //if the parent result has the same identifier as the child result, we join them
            //if it mant_to_x means array of arrays, else means one array
            if($intersectParentsKey !== false){
                //we don't need the parent identifier in the child result anymore
                unset($childResults[$childKey][$identifierAlias]);

                if($childNode->hasType(SmartFetchObjectManager::MANY_TO_ONE) ||
                    $childNode->hasType(SmartFetchObjectManager::MANY_TO_MANY)
                ){
                    $parentResults[$intersectParentsKey][$childFieldName][] = $childResults[$childKey];
                }else{
                    $parentResults[$intersectParentsKey][$childFieldName] = $childResults[$childKey];
                }

                //unset the child result from the child result array, because we don't need it anymore
                //cause it has been already joined in the parent result
                unset($childResults[$childKey]);
            }

        }

        //because we have unseted some result from childresult, we need to
        //update the result on the node, because we will use it later in another loop
        //so that will increase the performance like that
        $childNode->setResult($childResults);
    }
}