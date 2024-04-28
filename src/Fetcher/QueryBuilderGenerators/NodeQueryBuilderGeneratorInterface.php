<?php

    namespace Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators;

    use Doctrine\ORM\QueryBuilder;
    use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\Node;

    interface NodeQueryBuilderGeneratorInterface
    {
        public function generate(Node $node): QueryBuilder;

        public function support(Node $node): bool;

    }