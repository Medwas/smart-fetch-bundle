<?php

namespace Verclam\SmartFetchBundle\Fetcher\ResultsProcessors\Entity;

use Exception;
use Verclam\SmartFetchBundle\Fetcher\Hydrator\Entity\SmartFetchEntityHydratorInterface;
use Verclam\SmartFetchBundle\Fetcher\ResultsProcessors\ResultsProcessorInterface;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\CompositeNode;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\Node;

/**
 * In the case of the Entity mode every need fetched has
 * its own result, this class will join all the results of the
 * all the nodes to the root node.
 */
class ResultsProcessor implements ResultsProcessorInterface
{

    /**
     * @param iterable<SmartFetchEntityHydratorInterface> $hydrators
     */
    public function __construct(
        private readonly iterable $hydrators
    )
    {
    }

    /**
     * @throws Exception
     */
    public function processResult(Node $node, array &$result = []): void
    {
        if(!($node instanceof CompositeNode)){
            return;
        }

        foreach ($node->getChildren() as $child){
            foreach ($this->hydrators as $hydrator){
                if($hydrator->support($child)){
                    $hydrator->hydrate($child);
                    break;
                }
            }

            if($child instanceof CompositeNode){
                $this->processResult($child, $result);
            }
        }
    }
}