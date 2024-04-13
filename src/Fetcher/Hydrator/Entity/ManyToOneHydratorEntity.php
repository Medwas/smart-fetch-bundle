<?php

namespace Verclam\SmartFetchBundle\Fetcher\Hydrator\Entity;

use Verclam\SmartFetchBundle\Fetcher\Configuration\Configuration;
use Verclam\SmartFetchBundle\Fetcher\Hydrator\SmartFetchHydratorInterface;
use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Component;

class ManyToOneHydratorEntity implements SmartFetchHydratorInterface
{

    public function hydrate(Component $component): void
    {

        match (true){
            is_array($component->getParentResult())     => $this->hydrateArray($component),
            is_object($component->getParentResult())    => $this->hydrateEntity($component),
        };


    }

    private function hydrateEntity(Component $childNode): void
    {
        $childResult    = $childNode->getResult();
        $parentNode     = $childNode->getParent();
        $parentEntity   = $parentNode->getResult();
        $childProperty  = $childNode->getPropertyName();


        $collection     = $parentNode
            ->getReflectionProperty($childProperty)
            ->getValue($parentEntity);

        foreach($childResult as $child){
            $collection->hydrateAdd($child);
        }

        $collection->setInitialized(true);
        $collection->takeSnapshot();

        $childNode->setHasBeenHydrated(true);
    }

    private function hydrateArray(Component $childNode): void
    {
        $childResult    = $childNode->getResult();
        $parentNode     = $childNode->getParent();
        $parentResults  = $parentNode->getResult();

        $parentProperty     = $childNode->getParentProperty();
        $childProperty      = $childNode->getPropertyName();

        $parentPropertyReflexion    = $childNode->getReflectionProperty($parentProperty);
        $childPropertyReflexion     = $parentNode->getReflectionProperty($childNode->getPropertyName());

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

        $childNode->setHasBeenHydrated(true);
    }

    public function support(Component $component, Configuration $configuration): bool
    {
        return $component->hasType(SmartFetchObjectManager::MANY_TO_ONE)
            && $configuration->hasFetchMode(Configuration::ENTITY_FETCH_MODE)
            && !$component->hasBeenHydrated();
    }
}