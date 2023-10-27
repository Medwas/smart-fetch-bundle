<?php

    namespace Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators;

    use Doctrine\ORM\QueryBuilder;
    use Verclam\SmartFetchBundle\Fetcher\Configuration\Configuration;
    use Verclam\SmartFetchBundle\Fetcher\PropertyPaths\PropertyPaths;
    use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Component;

    interface QueryBuilderReverseGeneratorInterface
    {
        public function generate(Component $component, PropertyPaths $paths, QueryBuilder $queryBuilder): QueryBuilder;

    }