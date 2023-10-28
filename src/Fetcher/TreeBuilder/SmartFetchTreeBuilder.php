<?php

namespace Verclam\SmartFetchBundle\Fetcher\TreeBuilder;

use Error;
use Exception;
use RuntimeException;
use Verclam\SmartFetchBundle\Attributes\SmartFetch;
use Verclam\SmartFetchBundle\Fetcher\Configuration\Configuration;
use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\ComponentInterface;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Composite;

class SmartFetchTreeBuilder
{
    public function __construct(
        private readonly SmartFetchObjectManager $objectManager,
        private readonly ComponentFactory        $componentFactory,
    )
    {
    }

    /**
     * @throws Exception
     */
    public function buildTree(SmartFetch $smartFetch, Configuration $configuration): Composite
    {
        $joinEntities   = $smartFetch->getJoinEntities();
        $arrayTree      = $this->buildArrayTree($joinEntities);
        $classMetaData  = $this->objectManager->getClassMetadata($smartFetch->getClass());
        $parent         = $this->componentFactory->generate($classMetaData, $smartFetch, ComponentFactory::ROOT);
        $parent         = Composite::expect($parent);

        if (!($parent->isRoot())) {
            throw new Error('First parent must be a root');
        }

        //TODO: add support for the other fetch modes
        match ($configuration->getFetchMode()) {
            Configuration::ENTITY_FETCH_MODE => $this->buildEntityComponentTree($arrayTree, $parent, $smartFetch),
            default                          => throw new Error('Invalid fetch mode: ' . $configuration->getFetchMode()),
        };

        return $parent;
    }

    /**
     * @throws Exception
     */
    private function buildEntityComponentTree(
        array              $orderedArray,
        Composite          $component,
        SmartFetch         $smartFetch
    ): void
    {
        $metadata = $component->getClassMetadata();

        foreach ($orderedArray as $parent => $children) {
            if (!$metadata->hasAssociation($parent)) {
                throw new Error('Invalid join entity: ' . $parent . ' with class: ' . $metadata->getName());
            }

            $associationMapping = $metadata->getAssociationMapping($parent);
            $classMetaData      = $this->objectManager->getClassMetadata($associationMapping['targetEntity']);

            if (count($children) === 0) {
                $child = $this->componentFactory->generate($classMetaData, $smartFetch, ComponentFactory::LEAF, $associationMapping);
                $component->addChild($child);
                continue;
            }

            $composite = $this->componentFactory->generate($classMetaData, $smartFetch, ComponentFactory::COMPOSITE, $associationMapping);
            $composite = Composite::expect($composite);

            $component->addChild($composite);
            $this->buildEntityComponentTree($children, $composite, $smartFetch);
        }
    }

    private function buildArrayTree(array &$joinEntities, array &$parent = []): array
    {
        foreach ($joinEntities as $key => $joinEntity) {
            if (count($joinEntities) === 0) {
                return $parent;
            }

            $entities = explode('.', $joinEntity);

            if (count($entities) === 1) {
                [$field] = $entities;
                $parent[$field] = [];
                unset($joinEntities[$key]);
                $parent = $this->buildArrayTree($joinEntities, $parent);
                continue;
            }

            if (count($entities) > 2) {
                throw new RuntimeException('Invalid expression, you should not have more than this : field.subfield');
            }

            [$field, $subField] = $entities; // field.subField

            if (key_exists($field, $parent)) {
                $parent[$field][$subField] = [];
                unset($joinEntities[$key]);
                $parent[$field] = $this->buildArrayTree($joinEntities, $parent[$field]);
                continue;
            }

            foreach ($parent as $keyInner => $parentValue) {
                $parent[$keyInner] = $this->buildArrayTree($joinEntities, $parent[$keyInner]);
            }

        }

        return $parent;
    }

}