<?php

namespace Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Handlers;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Verclam\SmartFetchBundle\Attributes\SmartFetch;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\CompositeNode;

/**
 * Construct an array of the relation using the ClassMetadata
 * and the mappers "Serialization groups" or "Entity associations"
 * using one the services dedicated for each SmartFetch objectif:
 * -1- Fetch as an array.
 * -2- Fetch as Entity
 * -3- Fetch as a DTO
 */
class TreeBuilderHandler
{
    /**
     * @param iterable<int, TreeBuilderInterface> $handlers
     */
    public function __construct(
        private readonly iterable $handlers = [],
    )
    {
    }

    public function handle(SmartFetch $smartFetch, ClassMetadata $classMetaData): array
    {
        /** @var TreeBuilderInterface $handler */
        foreach ($this->handlers as $handler) {
            if ($handler->support($smartFetch)) {
                return $handler->handle($smartFetch, $classMetaData, true);
            }
        }

        throw new \Error('Unreachable');
    }
}