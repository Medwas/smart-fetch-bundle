<?php

namespace Verclam\SmartFetchBundle\Fetcher\TreeBuilder;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Exception;
use Verclam\SmartFetchBundle\Attributes\SmartFetch;
use Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\Attributes\FilterBy;
use Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\ConditionFactory;
use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\CompositeNode;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\LeafNode;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\Node;

class NodeFactory
{
    public const LEAF = 'leaf';
    public const COMPOSITE = 'composite';
    public const ROOT = 'root';

    public function __construct(
        private readonly ConditionFactory $conditionFactory,
    )
    {
    }

    /**
     * @param ClassMetadata $classMetadata
     * @param SmartFetch $smartFetch
     * @param string $type
     * @param ClassMetadata[] $successorClassMetadata
     * @param array $options
     * @return Node
     * @throws Exception
     */
    public function generate(
        ClassMetadata $classMetadata,
        SmartFetch $smartFetch,
        string $type,
        array $successorClassMetadata,
        array $options = [],
    ): Node
    {
        return match ($type) {
            self::LEAF => $this->generateLeaf($classMetadata, $options, $successorClassMetadata),
            self::COMPOSITE => $this->generateComposite($classMetadata, $options, $successorClassMetadata),
            self::ROOT => $this->generateRoot($classMetadata, $smartFetch, $successorClassMetadata),
            default => throw new Exception('Unknown type')
        };
    }

    /**
     * @param ClassMetadata $classMetadata
     * @param array $options
     * @param ClassMetadata[] $successorClassMetadatas
     * @return LeafNode
     */
    public function generateLeaf(
        ClassMetadata $classMetadata,
        array $options,
        array $successorClassMetadatas
    ): LeafNode
    {
        $leafNode = new LeafNode();
        $leafNode->setClassMetadata($classMetadata);
        $leafNode->setAlias($options['alias'] ?? $this->generateCommonAliases($options['fieldName']));
        $leafNode->setFieldName($options['fieldName']);
        $leafNode->setPropertyInformations($options);
        $leafNode->setInheritedClassMetadata($successorClassMetadatas);
        return $leafNode;
    }

    /**
     * @param ClassMetadata $classMetadata
     * @param array $options
     * @param ClassMetadata[] $successorClassMetadatas
     * @return CompositeNode
     */
    public function generateComposite(
        ClassMetadata $classMetadata,
        array $options,
        array $successorClassMetadatas
    ): CompositeNode
    {
        $compositeNode = new CompositeNode();
        $compositeNode->setClassMetadata($classMetadata);
        $compositeNode->setAlias($this->generateCommonAliases($options['fieldName']));
        $compositeNode->setFieldName($options['fieldName']);
        $compositeNode->setPropertyInformations($options);
        $compositeNode->setInheritedClassMetadata($successorClassMetadatas);
        return $compositeNode;
    }

    /**
     * @param ClassMetadata $classMetadata
     * @param SmartFetch $smartFetch
     * @param ClassMetadata[] $successorClassMetadata
     * @return CompositeNode
     * @throws Exception
     */
    public function generateRoot(
        ClassMetadata $classMetadata,
        SmartFetch $smartFetch,
        array $successorClassMetadata
    ): CompositeNode
    {
        $rootNode = new CompositeNode();
        $rootAlias = $this->generateRootAlias($smartFetch);

        $this->conditionFactory->generate($classMetadata, $smartFetch, $rootNode);

        $rootNode->setIsCollection($smartFetch->isCollection());
        $rootNode->setClassMetadata($classMetadata);
        $rootNode->setAlias($rootAlias);
        $rootNode->setFieldName($rootAlias);
        $rootNode->setPropertyInformations(['type' => SmartFetchObjectManager::ROOT]);
        $rootNode->setInheritedClassMetadata($successorClassMetadata);

        return $rootNode;
    }

    private function generateRootAlias(SmartFetch $smartFetch): string
    {
        $entityNameParts = explode('\\', $smartFetch->getClass());
        $entityName = end($entityNameParts);
        return strtolower($entityName);
    }

    private function generateCommonAliases(string $propertyName): string
    {
        return $propertyName[0] . $propertyName[-1] . '_a' . rand(0, PHP_INT_MAX);
    }
}