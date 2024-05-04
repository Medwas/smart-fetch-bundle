<?php

namespace Verclam\SmartFetchBundle\Fetcher\Hydrator\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Exception;
use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\Node;

class ManyToManyHydrator implements SmartFetchEntityHydratorInterface
{

    /**
     * @throws Exception
     */
    public function hydrate(Node $node): void
    {
//            if($node->isOwningSide()){
//                $node->setHasBeenHydrated(true);
//                return;
//            }
        $nodeResult = $node->getNodeResult();
        $result     = $nodeResult->getResult();
        $parentNode = $node->getParentNode();

        $parentNodeResult   = $parentNode->getNodeResult();
        $parentResults      = $parentNodeResult->getResult();

        $parentProperty = $node->getParentProperty();
        $parentPropertyReflexion = $node
            ->getClassMetadata()
            ->getReflectionProperty($parentProperty);

        $childPropertyReflexion = $parentNode->getClassMetadata()->getReflectionProperty($node->getFieldName());

        foreach($result as $child){
            $parents = $parentPropertyReflexion->getValue($child);
            foreach ($parents as $parentNode) {
                $collection = $childPropertyReflexion->getValue($parentNode);
                $collection->hydrateAdd($child);
            }
        }

        foreach ($parentResults as $parentResult){
            $collection = $childPropertyReflexion->getValue($parentResult);
            $collection->setInitialized(true);
            $collection->takeSnapshot();
        }

        $node->getNodeResult()->setHydrated(true);

    }

    public function support(Node $node): bool
    {
        return $node->hasType(SmartFetchObjectManager::MANY_TO_MANY)
            && !$node->getNodeResult()?->isHydrated();
    }
}