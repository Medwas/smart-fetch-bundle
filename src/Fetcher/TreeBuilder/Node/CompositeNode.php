<?php

namespace Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node;

use Verclam\SmartFetchBundle\Fetcher\Visitor\SmartFetchVisitorInterface;

class CompositeNode extends Node
{
    /**
     * @var Node[]
     */
    private array $children = [];
    private bool $isCollection = false;

    public function __construct()
    {
        parent::__construct();
    }

    public function isComposite(): bool
    {
        return true;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function addChild(NodeInterface $child): static
    {
        $this->children[] = $child;
        $child->setParentNode($this);
        return $this;
    }

    public function setChildren(array $children): static
    {
        $this->children = $children;

        return $this;
    }

    public function handle(SmartFetchVisitorInterface $visitor): void
    {
        if (null === $this->getNodeResult()) {
            $visitor->fetchResult($this);
        }

        foreach ($this->children as $child) {
            if($child->isScalar()){
                continue;
            }

            $child->handle($visitor);
        }
        
        //If this is a root node, it means here that
        //we reached the end of the tree, so we can now process the results
        //for example in arrayMode, we need to join the results
        // of all the nodes to the root node.
        if($this->isRoot()){
            $visitor->processResults($this);
        }

        $visitor->removeLastHistory();
    }

    public function isCollection(): bool
    {
        return $this->isCollection;
    }

    public function setIsCollection(bool $isCollection): void
    {
        $this->isCollection = $isCollection;
    }
}