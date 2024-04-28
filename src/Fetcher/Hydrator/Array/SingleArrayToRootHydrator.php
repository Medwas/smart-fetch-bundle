<?php

namespace Verclam\SmartFetchBundle\Fetcher\Hydrator\Array;

use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\CompositeNode;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\Node;

class SingleArrayToRootHydrator implements SmartFetchArrayHydratorInterface
{

    public function hydrate(Node $node, array &$parentResults): void
    {
        $parentResults = $node->getParentResult()?->getResult();

        $childResult = $node->getNodeResult()->getResult();
        $childResult = $childResult[array_key_first($childResult)];

        $propertyName                   = $node->getFieldName();
        $parentResults[$propertyName]   = $childResult;

        $node->getNodeResult()->setHydrated(true);
    }

    public function support(Node $node): bool
    {
        if(!($node instanceof CompositeNode) && $node->isFetchEager()){
            return false;
        }

        $parentNode = $node->getParentNode();
        return $parentNode->isRoot() && !$parentNode->isCollection() &&
            (
                $node->hasType(SmartFetchObjectManager::ONE_TO_ONE) ||
                $node->hasType(SmartFetchObjectManager::ONE_TO_MANY)
            );
    }
}