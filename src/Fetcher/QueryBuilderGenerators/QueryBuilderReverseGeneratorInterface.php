<?php

    namespace Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators;

    use Doctrine\ORM\QueryBuilder;
    use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\Node;

    interface QueryBuilderReverseGeneratorInterface
    {
        public function generate(Node $node, QueryBuilder $queryBuilder): QueryBuilder;

    }