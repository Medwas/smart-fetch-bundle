<?php

    namespace Verclam\SmartFetchBundle\Services;

    use Symfony\Component\HttpFoundation\Request;
    use Verclam\SmartFetchBundle\Attributes\SmartFetch;
    use Verclam\SmartFetchBundle\Fetcher\Configuration\Configuration;
    use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
    use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\SmartFetchTreeBuilder;
    use Verclam\SmartFetchBundle\Fetcher\Visitor\SmartFetchVisitorInterface;

    class SmartFetchEntityFetcher
    {

        /**
         * @param Configuration                                                 $configuration
         * @param SmartFetchObjectManager                                       $objectManager
         * @param iterable<mixed, SmartFetchVisitorInterface>                   $visitors
         */
        public function __construct(
            private readonly Configuration           $configuration,
            private readonly SmartFetchObjectManager $objectManager,
            private readonly SmartFetchTreeBuilder   $treeBuilder,
            private readonly iterable                $visitors,
        )
        {
        }

        /**
         * @param Request    $request
         * @param SmartFetch $attribute
         *
         * @return iterable
         * @throws \Exception
         */
        public function resolve(Request $request, SmartFetch $attribute): iterable
        {
            $queryName              = $attribute->getQueryName();
            $queryValue             = $request->attributes->get($queryName);
            $argumentName           = $attribute->getArgumentName();
            $attribute->setQueryValue($queryValue);

            if (\is_object($queryValue)) {
                return [];
            }

            if ($argumentName && \is_object($request->attributes->get($argumentName))) {
                return [];
            }

            if (!$attribute->getClass()) {
                return [];
            }

            if (!$this->objectManager->initObjectManager($attribute)) {
                return [];
            }

            if(empty($queryValue)) {
                throw new \LogicException(sprintf('Unable to guess how to get a Doctrine instance from the request information for parameter "%s".', $queryName));
            }

            //todo add the configuration
            $this->configuration->configure([ 'fetchMode' => Configuration::ENTITY_FETCH_MODE ]);

            $tree = $this->treeBuilder->buildTree($attribute, $this->configuration);

            foreach ($this->visitors as $visitor) {
                if(!$visitor->support($this->configuration)){
                    continue;
                }
                $visitor->start($tree);
                break;
            }

            return [$tree->getResult()];
        }

    }