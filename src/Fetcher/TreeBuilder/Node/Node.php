<?php

namespace Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node;

use Error;
use Verclam\SmartFetchBundle\Fetcher\Condition\Attributes\Condition;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Verclam\SmartFetchBundle\Fetcher\Condition\PropertyCondition;
use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
use Verclam\SmartFetchBundle\Fetcher\ResultsProcessors\NodeResult;
use Verclam\SmartFetchBundle\Fetcher\Visitor\SmartFetchVisitorInterface;

abstract class Node implements NodeInterface
{
    private ?Node $parentNode = null;
    private ?LeafNode $identifierNode = null;
    private ClassMetadata $classMetadata;
    private PropertyCondition $propertyCondition;

    private string $alias;
    private string $fieldName;
    private ?NodeResult $nodeResult = null;
    protected array $propertyInformations;
    /** @var ClassMetadata[]  */
    private array $inheritedClassMetadatas = [];
    private bool $fetchEager = false;

    public function __construct()
    {
        $this->propertyCondition = new PropertyCondition();
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

    public function getPropertyCondition(): PropertyCondition
    {
        return $this->propertyCondition;
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

    public function addCondition(Condition $condition): static
    {
        $this->propertyCondition->add($condition);
        return $this;
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
    public function getInheritedClassMetadatas(): array
    {
        return $this->inheritedClassMetadatas;
    }

    /**
     * @param ClassMetadata[] $classMetadatas
     * @return $this
     */
    public function setInheritedClassMetadatas(array $classMetadatas): static
    {
        $this->inheritedClassMetadatas = $classMetadatas;
        return $this;
    }

    /**
     * @param ClassMetadata $classMetadata
     * @return $this
     */
    public function addInheritedClassMetadata(ClassMetadata $classMetadata): static
    {
        $this->inheritedClassMetadatas[] = $classMetadata;
        return $this;
    }

    public function isSuccessorEntity(): bool
    {
        return count($this->inheritedClassMetadatas) > 0;
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

    abstract public function handle(SmartFetchVisitorInterface $visitor): void;
    abstract public function isComposite(): bool;

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

    abstract public function isCollection(): bool;
}