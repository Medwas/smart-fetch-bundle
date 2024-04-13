<?php

namespace Verclam\SmartFetchBundle\Fetcher\ResultsProcessors\Entity;

use Exception;
use Verclam\SmartFetchBundle\Fetcher\History\HistoryPaths;
use Verclam\SmartFetchBundle\Fetcher\Hydrator\SmartFetchHydratorInterface;
use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
use Verclam\SmartFetchBundle\Fetcher\ResultsProcessors\ResultsProcessorInterface;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Component;

/**
 * In the case of the Entity mode every need fetched has
 * its own result, this class will join all the results of the
 * all the nodes to the root node.
 */
class ResultsProcessor implements ResultsProcessorInterface
{
    private HistoryPaths $history;

    /**
     * @param iterable<SmartFetchHydratorInterface> $hydrators
     */
    public function __construct(
        private readonly iterable $hydrators
    )
    {
        $this->history = new HistoryPaths();
    }

    /**
     * @throws Exception
     */
    public function processResult(Component $component, array &$result = []): array
    {
        return $result;
    }

    /**
     * Check if an array has only null values
     * @param array $childResult
     * @return bool
     */
    private function isEmpty(array $childResult): bool
    {
        $childResultLength = count($childResult);
        $nullValuesCount = count(
            array_keys($childResult, null)
        );

        return $childResultLength === $nullValuesCount;
    }
}