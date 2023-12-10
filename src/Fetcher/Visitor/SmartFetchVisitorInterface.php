<?php

    namespace Verclam\SmartFetchBundle\Fetcher\Visitor;

    use Doctrine\ORM\QueryBuilder;
    use Verclam\SmartFetchBundle\Attributes\SmartFetch;
    use Verclam\SmartFetchBundle\Fetcher\Configuration\Configuration;
    use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Component;

    interface SmartFetchVisitorInterface
    {

        public function support(SmartFetch $smartFetch): bool;
        public function generate(Component $component);
        public function start(Component $component): void;
        public function addPath(Component $component): void;

        public function joinResult(Component $component): void;


    }