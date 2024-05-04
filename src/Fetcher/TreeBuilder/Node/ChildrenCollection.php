<?php

namespace Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node;

use Iterator;
use JetBrains\PhpStorm\ArrayShape;

class ChildrenCollection implements Iterator
{

    /** @var Node[]  */
    private array $children;

    /** @var array<string, int>  */
    private array $childrenPropertyInfo = [];
    private int $position = 0;

    public function current(): Node
    {
        return $this->children[$this->position];
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function key(): int
    {
        return $this->position;
    }

    public function valid(): bool
    {
        return isset($this->children[$this->position]);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function add(Node $child): void
    {
        $fieldName = $child->getFieldName();
        $className = $child->getClassName();

        $this->childrenPropertyInfo[$fieldName] = $this->position;

        $this->children[] = $child;
    }

    public function remove(Node $child): bool
    {
        $fieldName = $child->getFieldName();

        if(($key = $this->getKeyFromFieldName($fieldName)) === -1) {
            return false;
        }

        array_splice($this->children, $key, 1);
        array_splice($this->childrenPropertyInfo, $key, 1);
    }

    public function findChild(Node $child): ?Node
    {
        $fieldName = $child->getFieldName();

        if(($key = $this->getKeyFromFieldName($fieldName)) === -1) {
            return null;
        }

        return $this->children[$key];
    }

    public function getChildByFieldName(string $fieldName): ?Node
    {
        if(($key = $this->getKeyFromFieldName($fieldName)) === -1) {
            return null;
        }

        return $this->children[$key];
    }

    private function getKeyFromFieldName(string $fieldName): int
    {
        return $this->childrenPropertyInfo[$fieldName] ?? -1;
    }

    public function hasChildFieldName(string $fieldName): bool
    {
        return isset($this->childrenPropertyInfo[$fieldName]);
    }

    public function isEmpty(): bool
    {
        return count($this->children) === 0;
    }

    public function size(): int
    {
        return count($this->children);
    }

}