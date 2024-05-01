<?php

    namespace Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators;

    use Doctrine\ORM\QueryBuilder;
    use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\Node;

    interface QueryBuilderGeneratorInterface
    {
        public function generate(Node $node): QueryBuilder;

    }