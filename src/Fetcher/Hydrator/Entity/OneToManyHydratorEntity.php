<?php

    namespace Verclam\SmartFetchBundle\Fetcher\Hydrator\Entity;

    use Doctrine\ORM\PersistentCollection;
    use Verclam\SmartFetchBundle\Fetcher\Configuration\Configuration;
    use Verclam\SmartFetchBundle\Fetcher\Hydrator\SmartFetchHydratorInterface;
    use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
    use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Component;

    class OneToManyHydratorEntity implements SmartFetchHydratorInterface
    {

        public function hydrate(Component $component): void
        {
//            if(!$component->isOwningSide()){
//                $component->setHasBeenHydrated(true);
//                return;
//            }

            $result = $component->getResult();
            $parent = $component->getParent();
            $parentResults = $parent->getResult();

            $parentProperty = $component->getParentProperty();
            $parentPropertyReflexion = $component->getClassMetadata()->getReflectionProperty($parentProperty);

            $childPropertyReflexion = $parent->getClassMetadata()->getReflectionProperty($component->getPropertyName());

            foreach($result as $child){
                $parent = $parentPropertyReflexion->getValue($child);
                $collection = $childPropertyReflexion->getValue($parent);
                $collection->hydrateAdd($child);
            }

            foreach ($parentResults as $parentResult){
                $collection = $childPropertyReflexion->getValue($parentResult);
                $collection->setInitialized(true);
                $collection->takeSnapshot();
            }

            $component->setHasBeenHydrated(true);
        }

        public function support(Component $component, Configuration $configuration): bool
        {
            return $component->hasType(SmartFetchObjectManager::ONE_TO_MANY)
                && $configuration->hasFetchMode(Configuration::ENTITY_FETCH_MODE)
                && !$component->hasBeenHydrated();
        }
    }