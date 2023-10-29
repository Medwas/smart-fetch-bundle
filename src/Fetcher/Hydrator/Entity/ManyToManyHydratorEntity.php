<?php

    namespace Verclam\SmartFetchBundle\Fetcher\Hydrator\Entity;

    use Doctrine\Common\Collections\ArrayCollection;
    use Doctrine\ORM\PersistentCollection;
    use Verclam\SmartFetchBundle\Fetcher\Configuration\Configuration;
    use Verclam\SmartFetchBundle\Fetcher\Hydrator\SmartFetchHydratorInterface;
    use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
    use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Component;

    class ManyToManyHydratorEntity implements SmartFetchHydratorInterface
    {

        /**
         * @throws \Exception
         */
        public function hydrate(Component $component): void
        {
//            if($component->isOwningSide()){
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
                $parents = $parentPropertyReflexion->getValue($child);
                foreach ($parents as $parent) {
                    $collection = $childPropertyReflexion->getValue($parent);
                    $collection->hydrateAdd($child);
                }
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
            return $component->hasType(SmartFetchObjectManager::MANY_TO_MANY)
                && $configuration->hasFetchMode(Configuration::ENTITY_FETCH_MODE)
                && !$component->hasBeenHydrated();
        }
    }