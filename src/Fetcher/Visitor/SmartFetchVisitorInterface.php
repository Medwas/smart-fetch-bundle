<?php

    namespace Verclam\SmartFetchBundle\Fetcher\Visitor;

    use Doctrine\ORM\QueryBuilder;
    use Verclam\SmartFetchBundle\Fetcher\Configuration\Configuration;
    use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Component;

    interface SmartFetchVisitorInterface
    {

        public function support(Configuration $configuration): bool;
        public function generate(Component $component);
        public function start(Component $component): void;
        public function addPath(Component $component): void;


    }