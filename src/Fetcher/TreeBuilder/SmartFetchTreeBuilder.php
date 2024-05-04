<?php

namespace Verclam\SmartFetchBundle\Fetcher\TreeBuilder;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Error;
use Exception;
use Verclam\SmartFetchBundle\Attributes\SmartFetch;
use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\Node;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\CompositeNode;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\LeafNode;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Handlers\TreeBuilderHandler;

/**
 * Class SmartFetchTreeBuilder
 * Tree Builder for the smart fetch,
 * It consists of reading the ClassMetadata of the entity
 * build the tree of the relations as an array and then use this
 * array to build a tree of nodes that represent the relation
 * with all the information needed in every node that we need to fetch.
 */
class SmartFetchTreeBuilder
{
    /**
     * @param SmartFetchObjectManager $objectManager
     * @param NodeFactory $nodeFactory
     * @param TreeBuilderHandler $treeBuilderHandler
     */
    public function __construct(
        private readonly SmartFetchObjectManager    $objectManager,
        private readonly NodeFactory                $nodeFactory,
        private readonly TreeBuilderHandler         $treeBuilderHandler,
        private readonly FetchEagerManager          $fetchEagerManager
    ) {
    }

    /**
     * Building the complete tree of the fields needed for the smart fetch
     * @throws Exception
     */
    public function buildTree(SmartFetch $smartFetch): CompositeNode
    {
        $classMetaData = $this->objectManager->getClassMetadata($smartFetch->getClass());

        $arrayTree  = $this->treeBuilderHandler->handle($smartFetch, $classMetaData);
        $successorsClassMetadata = $this->fetchEagerManager
            ->retrieveSuccessorsClassMetadata($classMetaData);

        $parent     = $this->nodeFactory->generate(
            $classMetaData,
            $smartFetch,
            NodeFactory::ROOT,
            $successorsClassMetadata
        );
        $parent     = CompositeNode::expect($parent);

        if (!($parent->isRoot())) {
            throw new Error('First parent must be a root');
        }

        return $this->buildNodeTree($arrayTree, $parent, $smartFetch);
    }

    /**
     * Build the complete Nodes tree
     * of the fields needed for the smart fetch
     * Using the arrayTree
     * @throws Exception
     */
    private function buildNodeTree(
        array           $orderedArray,
        CompositeNode   $compositeNode,
        SmartFetch      $smartFetch
    ): CompositeNode {
        $metadata = $compositeNode->getClassMetadata();

        foreach ($orderedArray as $parentProperty => $childrenProperty) {
            if (!$metadata->hasField($parentProperty) && !$metadata->hasAssociation($parentProperty)) {
                throw new Error('Invalid join entity: ' . $parentProperty . ' with class: ' . $metadata->getName());
            }

            // Generate Scalar Node
            if (!$metadata->hasAssociation($parentProperty)) {
                $idIdentifier = $metadata->isIdentifier($parentProperty);

                $leafNode = $this
                    ->nodeFactory
                    ->generate(
                        $metadata,
                        $smartFetch,
                        NodeFactory::LEAF,
                        [],
                        [
                            'type'          => SmartFetchObjectManager::SCALAR,
                            'alias'         => $parentProperty,
                            'fieldName'     => $parentProperty,
                            'isIdentifier'  => $idIdentifier,
                        ]
                    );

                $leafNode = LeafNode::expect($leafNode);
                
                //Specify the scalar identifier to this compositeNode
                if ($idIdentifier) {
                    $compositeNode->setIdentifierNode($leafNode);
                }
                
                $compositeNode->addChild($leafNode);
                continue;
            }

            $associationMapping = $metadata->getAssociationMapping($parentProperty);
            $classMetaData = $this->objectManager->getClassMetadata($associationMapping['targetEntity']);

            $successorsClassMetadata = $this->fetchEagerManager
                ->retrieveSuccessorsClassMetadata($classMetaData);

            if (count($childrenProperty) === 0) {
                $leafNode = $this->createLeafNode(
                    $classMetaData,
                    $smartFetch,
                    $successorsClassMetadata,
                    $associationMapping,
                );

                $compositeNode->addChild($leafNode);
                $this->fetchEagerManager
                    ->hasLinkToParent($leafNode, $associationMapping);
                continue;
            }

            $composite = $this
                ->nodeFactory
                ->generate(
                    $classMetaData,
                    $smartFetch,
                    NodeFactory::COMPOSITE,
                    $successorsClassMetadata,
                    $associationMapping
                );

            $composite = CompositeNode::expect($composite);

            $compositeNode->addChild($composite);
            $this->buildNodeTree($childrenProperty, $composite, $smartFetch);
            $this->createCompositeFetchEager($composite, $classMetaData);
            $this->fetchEagerManager
                ->hasLinkToParent($compositeNode, $associationMapping);
        }

        return $compositeNode;
    }

    /**
     * @throws Exception
     */
    private function createLeafNode(
        ClassMetadata   $classMetadata,
        SmartFetch      $smartFetch,
        array           $successorsClassMetadata,
        array           $options = []
    ): Node {
        $type = NodeFactory::LEAF;
        $fetchEagerChildren = $this->fetchEagerManager
            ->retrieveFetchEagerEntities($classMetadata);

        if (count($fetchEagerChildren) > 0) {
            $type = NodeFactory::COMPOSITE;

            $compositeNode = $this->nodeFactory
                ->generateComposite(
                    $classMetadata,
                    $options,
                    $successorsClassMetadata,
                );

            $compositeNode = CompositeNode::expect($compositeNode);

            foreach ($fetchEagerChildren as $fetchEagerChild) {
                $childFetchEagerNode = $this->createLeafNode(
                    $fetchEagerChild['classMetadata'],
                    $smartFetch,
                    [],
                    $fetchEagerChild['options']
                );
                $childFetchEagerNode->setFetchEager(true);
                $compositeNode->addChild($childFetchEagerNode);
            }

            return $compositeNode;
        }

        $leafNode = $this->nodeFactory
            ->generateLeaf(
                $classMetadata,
                $options,
                $successorsClassMetadata,
            );

        return LeafNode::expect($leafNode);
    }

    /**
     * @throws Exception
     */
    private function createCompositeFetchEager(
        CompositeNode   $compositeNode,
        ClassMetadata   $classMetadata,
    ): void
    {
        $fetchEagerChildren = $this->fetchEagerManager
            ->retrieveFetchEagerEntities($classMetadata);

        if(count($fetchEagerChildren) === 0){
            return;
        }

        foreach ($fetchEagerChildren as $associationMapping){
            foreach ($compositeNode->getChildren() as $childNode){
                if($associationMapping['options']['fieldName'] === $childNode->getFieldName()){
                    $childNode->setFetchEager(true);
                    continue 2;
                }
            }
            $fetchEagerNode = $this->nodeFactory
                ->generateLeaf(
                    $associationMapping['classMetadata'],
                    $associationMapping['options'],
                    []
                );
            $fetchEagerNode->setFetchEager(true);
            $compositeNode->addChild($fetchEagerNode);
        }
    }
}
