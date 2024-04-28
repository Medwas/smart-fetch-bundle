<?php

namespace Verclam\SmartFetchBundle\Fetcher\Hydrator\Entity;

use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\Node;

class ManyToOneHydrator implements SmartFetchEntityHydratorInterface
{

    public function hydrate(Node $node): void
    {
        $parentResult = $node->getParentResult()?->getResult();

        match (true){
            is_array($parentResult)     => $this->hydrateArray($node),
            is_object($parentResult)    => $this->hydrateEntity($node),
            null                        => throw new \Exception('Invalid parent result!!')
        };


    }

    private function hydrateEntity(Node $childNode): void
    {
        $childNodeResult    = $childNode->getNodeResult();
        $childResult        = $childNodeResult->getResult();

        $parentNode         = $childNode->getParentNode();
        $parentNodeResult   = $parentNode->getNodeResult();
        $parentEntity       = $parentNodeResult->getResult();
        $childProperty  = $childNode->getFieldName();

        $collection     = $parentNode->getReflectionProperty($childProperty)
            ->getValue($parentEntity);

        foreach($childResult as $child){
            $collection->hydrateAdd($child);
        }

        $collection->setInitialized(true);
        $collection->takeSnapshot();

        $childNode->getNodeResult()->setHydrated(true);
    }

    private function hydrateArray(Node $childNode): void
    {
        $childNodeResult    = $childNode->getNodeResult();
        $childResult        = $childNodeResult->getResult();

        $parentNode         = $childNode->getParentNode();
        $parentNodeResults  = $parentNode->getNodeResult();
        $parentResults      = $parentNodeResults->getResult();

        $parentProperty     = $childNode->getParentProperty();
        $childProperty      = $childNode->getFieldName();

        $parentPropertyReflexion    = $childNode->getReflectionProperty($parentProperty);
        $childPropertyReflexion     = $parentNode->getReflectionProperty($childNode->getFieldName());

        foreach($childResult as $child){
            $parentNode = $parentPropertyReflexion->getValue($child);
            $collection = $childPropertyReflexion->getValue($parentNode);
            $collection->hydrateAdd($child);
        }

        foreach ($parentResults as $parentResult){
            $collection = $childPropertyReflexion->getValue($parentResult);
            $collection->setInitialized(true);
            $collection->takeSnapshot();
        }

        $childNode->getNodeResult()->setHydrated(true);
    }

    public function support(Node $node): bool
    {
        return $node->hasType(SmartFetchObjectManager::MANY_TO_ONE)
            && !$node->getNodeResult()?->isHydrated();
    }
}