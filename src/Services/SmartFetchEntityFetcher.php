<?php

namespace Verclam\SmartFetchBundle\Services;

use Exception;
use LogicException;
use ReflectionParameter;
use Symfony\Component\HttpFoundation\Request;
use Verclam\SmartFetchBundle\Attributes\SmartFetch;
use Verclam\SmartFetchBundle\Attributes\SmartFetchInterface;
use Verclam\SmartFetchBundle\Fetcher\Configuration\Configuration;
use Verclam\SmartFetchBundle\Fetcher\FilterPager\FilterPagerResolver;
use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\SmartFetchTreeBuilder;
use Verclam\SmartFetchBundle\Fetcher\Visitor\SmartFetchVisitorInterface;

class SmartFetchEntityFetcher
{

    /**
     * @param Configuration $configuration
     * @param SmartFetchObjectManager $objectManager
     * @param SmartFetchTreeBuilder $treeBuilder
     * @param ArgumentNameResolver $argumentNameResolver
     * @param iterable<int, SmartFetchVisitorInterface> $visitors
     */
    public function __construct(
        private readonly Configuration           $configuration,
        private readonly SmartFetchObjectManager $objectManager,
        private readonly SmartFetchTreeBuilder   $treeBuilder,
        private readonly ArgumentNameResolver    $argumentNameResolver,
        private readonly FilterPagerResolver     $filterPagerResolver,
        private readonly iterable                $visitors,
    )
    {
    }

    /**
     * @param Request $request
     * @param SmartFetch $smartFetch
     * @param array<ReflectionParameter>|null $reflexionParams
     * @return iterable
     * @throws Exception
     */
    public function resolve(Request $request, SmartFetch $smartFetch, array $reflexionParams = null): iterable
    {
        //TODO: Try to automatically connect the parameter name with the URL parameter
        //TODO: Try to automatically deduct the classname
        $this->argumentNameResolver->resolve($smartFetch, $request, $reflexionParams);
        if (!$smartFetch->isCollection()) {
            $queryName = $smartFetch->getQueryName();
            $queryValue = $request->attributes->get($queryName);
            $argumentName = $smartFetch->getArgumentName();
            $smartFetch->setQueryValue($queryValue);

            if (\is_object($queryValue)) {
                return [];
            }

            if ($argumentName && \is_object($request->attributes->get($argumentName))) {
                return [];
            }

            if (!$smartFetch->getClass()) {
                return [];
            }

            if (empty($queryValue)) {
                throw new LogicException(
                    sprintf(
                        'Unable to guess how to get a Doctrine instance from the request information for parameter "%s".',
                        $queryName
                    )
                );
            }
        }

        if (!$this->objectManager->initObjectManager($smartFetch)) {
            return [];
        }

        //TODO: add the configuration
        $this->configuration->configure([]);

        $this->resolveFilterPager($request, $smartFetch);

        $tree = $this->treeBuilder->buildTree($smartFetch);

        foreach ($this->visitors as $visitor) {
            if (!$visitor->support($smartFetch)) {
                continue;
            }
            $visitor->visit($tree);
            break;
        }

        return [$tree->getNodeResult()->getResult()];
    }

    private function resolveFilterPager(Request $request, SmartFetch $smartFetch): void
    {
        $filterPager = $this->filterPagerResolver->resolve($request, $smartFetch);

        if(null === $filterPager){
            return;
        }

        $smartFetch->setFilterPager($filterPager);
    }

}