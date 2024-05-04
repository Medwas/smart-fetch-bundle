<?php

namespace Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Error;
use Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\Attributes\SmartFetchConditionInterface;
use Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\FieldCondition;
use Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\FieldConditionCollection;
use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
use Verclam\SmartFetchBundle\Fetcher\ResultsProcessors\NodeResult;
use Verclam\SmartFetchBundle\Fetcher\Visitor\SmartFetchVisitorInterface;

abstract class Node implements NodeInterface
{
    private ?Node $parentNode = null;
    private ?LeafNode $identifierNode = null;
    private ClassMetadata $classMetadata;
    private FieldConditionCollection $fieldConditionCollection;

    private string $alias;
    private string $fieldName;
    private ?NodeResult $nodeResult = null;
    protected array $propertyInformations;
    /** @var ClassMetadata[]  */
    private array $inheritedClassMetadata = [];
    private bool $fetchEager = false;

    /**
     * @var Node[]
     */
    protected ChildrenCollection $children;
    private bool $isCollection = false;

    public function __construct()
    {
        $this->fieldConditionCollection = new FieldConditionCollection();
        $this->children = new ChildrenCollection();
    }

    public function isRoot(): bool
    {
        return null === $this->parentNode;
    }

    public function setParentNode(Node $parentNode): static
    {
        $this->parentNode = $parentNode;
        return $this;
    }

    public function getParentNode(): ?Node
    {
        return $this->parentNode;
    }

    public function getClassMetadata(): ClassMetadata
    {
        return $this->classMetadata;
    }

    public function getFieldConditionCollection(): FieldConditionCollection
    {
        return $this->fieldConditionCollection;
    }

    public function addFieldCondition(FieldCondition $condition): static
    {
        $this->fieldConditionCollection->add($condition);
        return $this;
    }

     public function hasType(int $type): bool
     {
         return $this->propertyInformations['type'] === $type;
     }

    public function isScalar(): bool
    {
        return $this->propertyInformations['type'] === SmartFetchObjectManager::SCALAR;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function setAlias(string $alias): static
    {
        $this->alias = $alias;
        return $this;
    }

    public function setFieldName(string $fieldName): static
    {
        $this->fieldName = $fieldName;
        return $this;
    }

    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    public function getReflectionProperty(string $propertyName): \ReflectionProperty
    {
        return $this->classMetadata->getReflectionProperty($propertyName);
    }

    public function getNodeResult(): ?NodeResult
    {
        return $this->nodeResult;
    }

    public function setNodeResult(NodeResult $nodeResult): static
    {
        $this->nodeResult = $nodeResult;
        return $this;
    }

    public function getParentResult(): null|NodeResult
    {
        return $this->parentNode->getNodeResult();
    }

    public function setClassMetadata(ClassMetadata $classMetadata): Node
    {
        $this->classMetadata = $classMetadata;
        return $this;
    }

    public function setPropertyInformations(array $propertyInformations): static
    {
        $this->propertyInformations = $propertyInformations;
        return $this;
    }

    public function isOwningSide(): bool
    {
        return !$this->propertyInformations['isOwningSide'];
    }

    public function isIdentifier(): bool{
        return key_exists('isIdentifier', $this->propertyInformations) && $this->propertyInformations['isIdentifier'] === true;

    }

    public function getParentProperty(): string
    {
        return !$this->isOwningSide()
            ? $this->propertyInformations['inversedBy']
            : $this->propertyInformations['mappedBy'];
    }

    public function getRelationType(): int
    {
        return $this->propertyInformations['type'];
    }

    public function getClassName(): string
    {
        return $this->classMetadata->getName();
    }

    /**
     * @return ClassMetadata[]
     */
    public function getInheritedClassMetadata(): array
    {
        return $this->inheritedClassMetadata;
    }

    /**
     * @param ClassMetadata[] $classMetadatas
     * @return $this
     */
    public function setInheritedClassMetadata(array $classMetadatas): static
    {
        $this->inheritedClassMetadata = $classMetadatas;
        return $this;
    }

    /**
     * @param ClassMetadata $classMetadata
     * @return $this
     */
    public function addInheritedClassMetadata(ClassMetadata $classMetadata): static
    {
        $this->inheritedClassMetadata[] = $classMetadata;
        return $this;
    }

    public function isSuccessorEntity(): bool
    {
        return count($this->inheritedClassMetadata) > 0;
    }

    public static function expect($object): static
    {
        if (!is_a($object, static::class)) {
            throw new Error(sprintf('Object must be a %s but got %s', static::class, $object::class));
        }

        return $object;
    }

    public function __toString(): string
    {
        return $this->fieldName;
    }

    public function getIdentifierNode(): ?LeafNode
    {
        return $this->identifierNode;
    }

    public function setIdentifierNode(?LeafNode $identifierNode): static
    {
        $this->identifierNode = $identifierNode;
        
        return $this;
    }

    public function setFetchEager(bool $fetchEager): static
    {
        $this->fetchEager = $fetchEager;

        return $this;
    }

    public function isFetchEager(): bool
    {
        return $this->fetchEager;
    }

    public function getChildren(): ChildrenCollection
    {
        return $this->children;
    }

    public function addChild(Node $child): static
    {
        $this->children->add($child);
        $child->setParentNode($this);
        $this->childrenFieldNames[$child->getAlias()] = $child->getFieldName();
        return $this;
    }

    public function hasChildFieldName(string $fieldName): bool
    {
        return $this->children->hasChildFieldName($fieldName);
    }

    public function getChildByFieldName(string $fieldName): ?Node
    {
        return $this->children->getChildByFieldName($fieldName);
    }

    public function isComposite(): bool
    {
        return $this->children->isEmpty();
    }

    public function isCollection(): bool
    {
        return $this->isCollection;
    }

    public function setIsCollection(bool $isCollection): void
    {
        $this->isCollection = $isCollection;
    }

    abstract public function handle(SmartFetchVisitorInterface $visitor): void;
}