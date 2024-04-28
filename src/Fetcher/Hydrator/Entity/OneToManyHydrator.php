<?php

namespace Verclam\SmartFetchBundle\Fetcher\Hydrator\Entity;

use Doctrine\ORM\PersistentCollection;
use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\Node;

class OneToManyHydrator implements SmartFetchEntityHydratorInterface
{


    public function hydrate(Node $node): void
    {
        // TODO: Implement hydrate() method.
    }

    public function support(Node $node): bool
    {
        return $node->hasType(SmartFetchObjectManager::ONE_TO_MANY)
            && !$node->getNodeResult()?->isHydrated();
    }
}