<?php

namespace Verclam\SmartFetchBundle\Fetcher\TreeBuilder;

use Exception;
use Verclam\SmartFetchBundle\Attributes\SmartFetch;
use Verclam\SmartFetchBundle\Fetcher\Condition\Attributes\Condition;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Verclam\SmartFetchBundle\Fetcher\Condition\ConditionFactory;
use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\Node;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\CompositeNode;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\LeafNode;

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
     * @param ClassMetadata[] $successorClassMetadatas
     * @param array $options
     * @return Node
     * @throws Exception
     */
    public function generate(
        ClassMetadata $classMetadata,
        SmartFetch $smartFetch,
        string $type,
        array $successorClassMetadatas,
        array $options = [],
    ): Node
    {
        return match ($type) {
            self::LEAF => $this->generateLeaf($classMetadata, $options, $successorClassMetadatas),
            self::COMPOSITE => $this->generateComposite($classMetadata, $options, $successorClassMetadatas),
            self::ROOT => $this->generateRoot($classMetadata, $smartFetch, $successorClassMetadatas),
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
        $leafNode->setInheritedClassMetadatas($successorClassMetadatas);
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
        $compositeNode->setInheritedClassMetadatas($successorClassMetadatas);
        return $compositeNode;
    }

    /**
     * @param ClassMetadata $classMetadata
     * @param SmartFetch $smartFetch
     * @param ClassMetadata[] $successorClassMetadatas
     * @return CompositeNode
     * @throws Exception
     */
    public function generateRoot(
        ClassMetadata $classMetadata,
        SmartFetch $smartFetch,
        array $successorClassMetadatas
    ): CompositeNode
    {
        $rootNode = new CompositeNode();
        $rootAlias = $this->generateRootAlias($smartFetch);

        if (!$smartFetch->isCollection()) {
            $condition = $this->conditionFactory->generate(
                [
                    'type' => ConditionFactory::FILTER_BY,
                    'property' => $classMetadata->getIdentifier()[0],
                    'operator' => Condition::EQUAL,
                    'value' => $smartFetch->getQueryValue()
                ]
            );
            $rootNode->addCondition($condition);
        }

        $rootNode->setIsCollection($smartFetch->isCollection());
        $rootNode->setClassMetadata($classMetadata);
        $rootNode->setAlias($rootAlias);
        $rootNode->setFieldName($rootAlias);
        $rootNode->setPropertyInformations(['type' => SmartFetchObjectManager::ROOT]);
        $rootNode->setInheritedClassMetadatas($successorClassMetadatas);

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