<?php

    namespace Verclam\SmartFetchBundle\Fetcher\TreeBuilder;

    use Verclam\SmartFetchBundle\Attributes\SmartFetch;
    use Verclam\SmartFetchBundle\Fetcher\Configuration\Configuration;
    use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
    use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\ComponentInterface;
    use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Composite;

    class SmartFetchTreeBuilder
    {
        public function __construct(
            private readonly SmartFetchObjectManager    $objectManager,
            private readonly ComponentFactory           $componentFactory,
        )
        {
        }

        /**
         * @throws \Exception
         */
        public function buildTree(SmartFetch $attribute, Configuration $configuration): Composite
        {
            $joinEntities = $attribute->getJoinEntities();
            $arrayTree = $this->buildArrayTree($joinEntities);

            $classMetaData = $this->objectManager->getClassMetadata($attribute->getClass());
            $parent = $this->componentFactory->generate($classMetaData, $attribute, ComponentFactory::ROOT);

            if(!($parent instanceof Composite)){
                throw new \Error('First parent must be a composite');
            }

            if(!($parent->isRoot())){
                throw new \Error('First parent must be a root');
            }

            //TODO: add support for the other fetch modes
            match ($configuration->getFetchMode()){
                Configuration::ENTITY_FETCH_MODE => $this->buildEntityComponentTree($arrayTree, $parent, $attribute),
                default => throw new \Error('Invalid fetch mode: ' . $configuration->getFetchMode()),

            };

            return $parent;
        }

        /**
         * @throws \Exception
         */
        private function buildEntityComponentTree(
            array               $orderedArray,
            ComponentInterface  $component,
            SmartFetch          $attribute
        ): void
        {
            $metadata = $component->getClassMetadata();
            foreach ($orderedArray as $key => $value){
                if(!$metadata->hasAssociation($key)){
                    throw new \Error('Invalid join entity: ' . $key . ' with class: ' . $metadata->getName());
                }

                $associationMapping = $metadata->getAssociationMapping($key);
                $classMetaData = $this->objectManager->getClassMetadata($associationMapping['targetEntity']);

                if(count($value) === 0){
                    $child = $this->componentFactory->generate($classMetaData, $attribute, ComponentFactory::LEAF, $associationMapping);
                    $component->addChild($child);
                    continue;
                }

                $composite = $this->componentFactory->generate($classMetaData, $attribute, ComponentFactory::COMPOSITE, $associationMapping);
                $component->addChild($composite);
                $this->buildEntityComponentTree($value , $composite, $attribute);
            }
        }

        private function buildArrayTree(array &$joinEntities, array &$parent = []): array
        {
            foreach ($joinEntities as $key =>  $joinEntity){
                if(count($joinEntities) === 0){
                    return $parent;
                }
                $entities = explode('.', $joinEntity);
                if(count($entities) === 1){
                    $parent[(string) $entities[0]] = [];
                    unset($joinEntities[$key]);
                    $parent = $this->buildArrayTree($joinEntities, $parent);
                    continue;
                }

                if(key_exists($entities[0], $parent)){
                    $parent[(string) $entities[0]][(string) $entities[1]] =  [];
                    unset($joinEntities[$key]);
                    $parent[(string) $entities[0]] = $this->buildArrayTree($joinEntities, $parent[(string) $entities[0]]);
                    continue;
                }
                foreach ($parent as $keyInner => $parentValue){
                    $parent[$keyInner] = $this->buildArrayTree($joinEntities, $parent[$keyInner]);
                }

            }

            return $parent;
        }


    }