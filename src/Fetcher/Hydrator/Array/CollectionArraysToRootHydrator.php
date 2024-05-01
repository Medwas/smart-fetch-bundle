<?php

namespace Verclam\SmartFetchBundle\Fetcher\Hydrator\Array;

use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\CompositeNode;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\Node;

class CollectionArraysToRootHydrator implements SmartFetchArrayHydratorInterface
{

    public function hydrate(Node $node, array &$parentResults): void
    {
        $childResult = $node->getNodeResult()->getResult();

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
                $node->hasType(SmartFetchObjectManager::MANY_TO_MANY) ||
                $node->hasType(SmartFetchObjectManager::MANY_TO_ONE)
            );
    }
}