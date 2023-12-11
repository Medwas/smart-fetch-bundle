<?php

namespace Verclam\SmartFetchBundle\Fetcher\TreeBuilder;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Error;
use Exception;
use Verclam\SmartFetchBundle\Attributes\SmartFetch;
use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Composite;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Handlers\TreeBuilderHandler;

/**
 * Class SmartFetchTreeBuilder
 * Tree Builder for the smart fetch
 */
class SmartFetchTreeBuilder
{
    /**
     * @param SmartFetchObjectManager $objectManager
     * @param ComponentFactory $componentFactory
     * @param TreeBuilderHandler $treeBuilderHandler
     */
    public function __construct(
        private readonly SmartFetchObjectManager $objectManager,
        private readonly ComponentFactory        $componentFactory,
        private readonly TreeBuilderHandler      $treeBuilderHandler,
    )
    {
    }

    /**
     * Building the complete tree of the fields needed for the smart fetch
     * @throws Exception
     */
    public function buildTree(SmartFetch $smartFetch): Composite
    {
        $classMetaData = $this->objectManager->getClassMetadata($smartFetch->getClass());

        $arrayTree  = $this->treeBuilderHandler->handle($smartFetch, $classMetaData);
        $parent     = $this->componentFactory->generate($classMetaData, $smartFetch, ComponentFactory::ROOT);
        $parent     = Composite::expect($parent);

        if (!($parent->isRoot())) {
            throw new Error('First parent must be a root');
        }

        $this->buildEntityComponentTree($arrayTree, $parent, $smartFetch);

        return $parent;
    }

    /**
     * Build the complete tree of the fields needed for the smart fetch
     * Using the arrayTree
     * @throws Exception
     */
    private function buildEntityComponentTree(
        array      $orderedArray,
        Composite  $component,
        SmartFetch $smartFetch
    ): void
    {
        $metadata = $component->getClassMetadata();

        foreach ($orderedArray as $parent => $children) {
            if (!$metadata->hasField($parent) &&
            !$metadata->hasAssociation($parent)) {
                throw new Error('Invalid join entity: ' . $parent . ' with class: ' . $metadata->getName());
            }

            if(!$metadata->hasAssociation($parent)){
                $child = $this
                    ->componentFactory
                    ->generate(
                        $metadata,
                        $smartFetch,
                        ComponentFactory::LEAF,
                        [
                            'type'          => SmartFetchObjectManager::SCALAR,
                            'alias'         => $parent,
                            'fieldName'     => $parent,
                        ]
                    );

                $component->addChild($child);
                continue;
            }

            $associationMapping = $metadata->getAssociationMapping($parent);
            $classMetaData = $this->objectManager->getClassMetadata($associationMapping['targetEntity']);

            if (count($children) === 0) {
                $child = $this
                    ->componentFactory
                    ->generate($classMetaData, $smartFetch, ComponentFactory::LEAF, $associationMapping);

                $component->addChild($child);
                continue;
            }

            $composite = $this
                ->componentFactory
                ->generate($classMetaData, $smartFetch, ComponentFactory::COMPOSITE, $associationMapping);

            $composite = Composite::expect($composite);

            $component->addChild($composite);
            $this->buildEntityComponentTree($children, $composite, $smartFetch);
        }
    }
}