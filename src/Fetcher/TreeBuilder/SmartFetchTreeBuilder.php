<?php

namespace Verclam\SmartFetchBundle\Fetcher\TreeBuilder;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Error;
use Exception;
use Verclam\SmartFetchBundle\Attributes\SmartFetch;
use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Composite;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Leaf;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Handlers\TreeBuilderHandler;

/**
 * Class SmartFetchTreeBuilder
 * Tree Builder for the smart fetch,
 * It consists of reading the ClassMetadata of the entity
 * build the tree of the relations as an array and then use this
 * array to build a tree of Component that represent the relation
 * with all the information needed in every node that we need to fetch.
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
    ) {
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

        return $this->buildEntityComponentTree($arrayTree, $parent, $smartFetch);
    }

    /**
     * Build the complete Component Nodes tree
     * of the fields needed for the smart fetch
     * Using the arrayTree
     * @throws Exception
     */
    private function buildEntityComponentTree(
        array      $orderedArray,
        Composite  $component,
        SmartFetch $smartFetch
    ): Composite {
        $metadata = $component->getClassMetadata();

        foreach ($orderedArray as $parent => $children) {
            if (!$metadata->hasField($parent) && !$metadata->hasAssociation($parent)) {
                throw new Error('Invalid join entity: ' . $parent . ' with class: ' . $metadata->getName());
            }

            if (!$metadata->hasAssociation($parent)) {
                $leaf = $this
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

                $component->addChild($leaf);
                continue;
            }

            $associationMapping = $metadata->getAssociationMapping($parent);
            $classMetaData = $this->objectManager->getClassMetadata($associationMapping['targetEntity']);

            $fetchEagerChildren = [];
            
            foreach ($classMetaData->getAssociationMappings() as $insideAssociationMapping) {
                $insideClassMetadata = $this->objectManager
                    ->getClassMetadata($insideAssociationMapping['targetEntity']);

                if (($insideAssociationMapping['type'] === SmartFetchObjectManager::ONE_TO_ONE)
                    && !$insideAssociationMapping['isOwningSide']) {
                    $fetchEagerChildren[] = $this->componentFactory
                        ->generate(
                            $insideClassMetadata,
                            $smartFetch,
                            ComponentFactory::LEAF,
                            $insideAssociationMapping
                        );
                    continue;
                }


                if (($insideAssociationMapping['type'] === SmartFetchObjectManager::ONE_TO_MANY) &&
                    count($insideClassMetadata->subClasses) > 0) {
                    $fetchEagerChildren[] = $this->componentFactory
                        ->generate(
                            $insideClassMetadata,
                            $smartFetch,
                            ComponentFactory::LEAF,
                            $insideAssociationMapping
                        );
                }
            }

            if (count($children) === 0) {
                $leaf = $this
                    ->componentFactory
                    ->generate($classMetaData, $smartFetch, ComponentFactory::LEAF, $associationMapping);

                $leaf = Leaf::expect($leaf);

                $leaf->setFetchEagerChildren($fetchEagerChildren);
                $component->addChild($leaf);
                continue;
            }

            $composite = $this
                ->componentFactory
                ->generate($classMetaData, $smartFetch, ComponentFactory::COMPOSITE, $associationMapping);

            $composite = Composite::expect($composite);

            $composite->setFetchEagerChildren($fetchEagerChildren);

            $component->addChild($composite);
            $this->buildEntityComponentTree($children, $composite, $smartFetch);
        }

        return $component;
    }
}
