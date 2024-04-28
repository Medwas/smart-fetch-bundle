<?php

    namespace Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators;

    use Doctrine\ORM\QueryBuilder;
    use Verclam\SmartFetchBundle\Fetcher\History\HistoryPaths;
    use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\Node;

    interface NodeQueryBuilderGeneratorInterface
    {
        public function generate(Node $node, HistoryPaths $paths): QueryBuilder;

        public function support(Node $node): bool;

    }