<?php

namespace Verclam\SmartFetchBundle\Services;

use Exception;
use Symfony\Component\HttpFoundation\Request;
use Verclam\SmartFetchBundle\Attributes\SmartFetch;
use Verclam\SmartFetchBundle\Attributes\SmartFetchInterface;
use Verclam\SmartFetchBundle\Enum\FetchModeEnum;
use Verclam\SmartFetchBundle\Fetcher\Configuration\Configuration;
use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
use Verclam\SmartFetchBundle\Fetcher\ResultsJoiner\Array\ResultsJoiner;
use Verclam\SmartFetchBundle\Fetcher\ResultsJoiner\ResultsJoinerInterface;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\SmartFetchTreeBuilder;
use Verclam\SmartFetchBundle\Fetcher\Visitor\SmartFetchVisitorInterface;

class SmartFetchEntityFetcher
{

    /**
     * @param Configuration $configuration
     * @param SmartFetchObjectManager $objectManager
     * @param SmartFetchTreeBuilder $treeBuilder
     * @param iterable<int, SmartFetchVisitorInterface> $visitors
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
     * @param Request $request
     * @param SmartFetch $smartFetch
     *
     * @return iterable
     * @throws Exception
     */
    public function resolve(Request $request, SmartFetch $smartFetch): iterable
    {
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

        if (!$this->objectManager->initObjectManager($smartFetch)) {
            return [];
        }

        if (empty($queryValue)) {
            throw new \LogicException(sprintf('Unable to guess how to get a Doctrine instance from the request information for parameter "%s".', $queryName));
        }

        //todo add the configuration
        $this->configuration->configure(['fetchMode' => FetchModeEnum::ARRAY]);

        $tree = $this->treeBuilder->buildTree($smartFetch);

        foreach ($this->visitors as $visitor) {
            if (!$visitor->support($smartFetch)) {
                continue;
            }
            $visitor->start($tree);
            break;
        }

        return [$tree->getResult()];
    }

}