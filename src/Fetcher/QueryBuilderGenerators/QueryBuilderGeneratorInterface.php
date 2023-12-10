<?php

    namespace Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators;

    use Doctrine\ORM\QueryBuilder;
    use Verclam\SmartFetchBundle\Fetcher\History\HistoryPaths;
    use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Component;

    interface QueryBuilderGeneratorInterface
    {
        public function generate(Component $component, HistoryPaths $paths): QueryBuilder;

    }