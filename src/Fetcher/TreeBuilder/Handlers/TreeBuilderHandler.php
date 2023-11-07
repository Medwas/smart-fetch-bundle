<?php

namespace Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Handlers;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Verclam\SmartFetchBundle\Attributes\SmartFetch;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Composite;

class TreeBuilderHandler
{
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
                return $handler->handle($smartFetch, $classMetaData);
            }
        }

        throw new \Error('Unreachable');
    }
}