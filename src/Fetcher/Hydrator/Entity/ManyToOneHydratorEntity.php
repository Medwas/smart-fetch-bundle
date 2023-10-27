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
            // TODO: Implement hydrate() method.
        }

        public function support(Component $component, Configuration $configuration): bool
        {
            return $component->hasType(SmartFetchObjectManager::MANY_TO_ONE)
                && $configuration->hasFetchMode(Configuration::ENTITY_FETCH_MODE)
                && !$component->hasBeenHydrated();
        }
    }