<?php

namespace Verclam\SmartFetchBundle\Fetcher\Hydrator\Array;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Exception;
use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\Node;

class CollectionHydrator implements SmartFetchArrayHydratorInterface
{

    /**
     * Join the parent result with the child result based on the identifier
     *  that has been added in every child result
     * @param Node $node
     * @param array $parentResults
     * @return void
     * @throws Exception
     */
    public function hydrate(Node $node, array &$parentResults): void
    {
        //if the parent result is empty, we don't need to join anything
        if(count($parentResults) === 0){
            return;
        }

        $nodeResult         = $node->getNodeResult();
        $childArrayResults  = $nodeResult->getResult();
        $parentNode         = $node->getParentNode();

        $parentIdentifier = $parentNode->getClassMetadata()->getIdentifier();

        if(count($parentIdentifier) > 1){
            throw new Exception(
                'Composite keys are not supported, Doctrine\'s best practice, says that it is better to avoid using it'
            );
        }

        $parentIdentifier   = $parentIdentifier[0];
        $identifierAlias    = $parentNode->getAlias() . '_' . $parentIdentifier;
        $childFieldName     = $node->getFieldName();

        //retrieve only the identifier column from the parent result to avoid make a double loop on the parent result
        $parentsIdentifierColumn = array_column($parentResults, $parentIdentifier);

        foreach ($childArrayResults as $childKey => $childResult){
            //retrieve the key of the parent result that has the same identifier as the child result
            $intersectParentsKey = array_search($childResult[$identifierAlias], $parentsIdentifierColumn, true);

            //if the parent result has the same identifier as the child result, we join them
            //if it mant_to_x means array of arrays, else means one array
            if($intersectParentsKey !== false){
                //we don't need the parent identifier in the child result anymore
                unset($childArrayResults[$childKey][$identifierAlias]);

                //check if the current result has only null values, in this case we need to remove it
                if($this->isEmpty($childArrayResults[$childKey])){
                    unset($childArrayResults[$childKey]);
                    $parentResults[$intersectParentsKey][$childFieldName] = [];
                    continue;
                }

                if($node->hasType(SmartFetchObjectManager::MANY_TO_ONE) ||
                    $node->hasType(SmartFetchObjectManager::MANY_TO_MANY)
                ){
                    $parentResults[$intersectParentsKey][$childFieldName][] = $childArrayResults[$childKey];
                }else{
                    $parentResults[$intersectParentsKey][$childFieldName] = $childArrayResults[$childKey];
                }

                //unset the child result from the child result array, because we don't need it anymore
                //cause it has been already joined in the parent result
                unset($childArrayResults[$childKey]);
            }

        }

        //because we have unseted some result from childresult, we need to
        //update the result on the node, because we will use it later in another loop
        //so that will increase the performance like that
        $nodeResult->setResult($childArrayResults);
        $node->setNodeResult($nodeResult);

        $node->getNodeResult()->setHydrated(true);
    }

    /**
     * Check if an array has only null values
     * @param array $childResult
     * @return bool
     */
    private function isEmpty(array $childResult): bool
    {
        $childResultLength = count($childResult);
        $nullValuesCount = count(
            array_keys($childResult, null)
        );

        return $childResultLength === $nullValuesCount;
    }

    public function support(Node $node): bool
    {
        $parentNode = $node->getParentNode();
        return $parentNode->isCollection() || !$parentNode->isRoot();
    }
}