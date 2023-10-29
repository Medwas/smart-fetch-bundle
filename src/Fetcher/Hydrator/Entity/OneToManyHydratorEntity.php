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
            // TODO: Implement hydrate() method.
        }

        public function support(Component $component, Configuration $configuration): bool
        {
            return $component->hasType(SmartFetchObjectManager::ONE_TO_MANY)
                && $configuration->hasFetchMode(Configuration::ENTITY_FETCH_MODE)
                && !$component->hasBeenHydrated();
        }
    }