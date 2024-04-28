<?php

namespace Verclam\SmartFetchBundle\Fetcher\ResultsProcessors\Array;

use Exception;
use Verclam\SmartFetchBundle\Fetcher\Hydrator\Array\SmartFetchArrayHydratorInterface;
use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
use Verclam\SmartFetchBundle\Fetcher\ResultsProcessors\ResultsProcessorInterface;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\CompositeNode;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\Node;

/**
 * In the case of the Array mode every need fetched has
 * its own result, this class will join all the results of the
 * all the nodes to the root node.
 */
class ResultsProcessor implements ResultsProcessorInterface
{

    /**
     * @param iterable<SmartFetchArrayHydratorInterface> $hydrators
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
        
        /** @var Node $child */
        foreach ($node->getChildren() as $child){
            if($child->isScalar()){
                continue;
            }

            $isRealLastChild = $this->isLastChild($child);

            if($child instanceof CompositeNode && $isRealLastChild){
                foreach ($this->hydrators as $hydrator){
                    if($hydrator->support($child)){
                        $hydrator->hydrate($child, $result);
                        $node->getNodeResult()->setResult($result);
                        break;
                    }
                }
            }

            if(!$isRealLastChild){
                $childResult = $child->getNodeResult()?->getResult();

                if(null === $childResult){
                    throw new Exception('Child result should never be null at this point!!');
                }

                $this->processResult($child, $childResult);
                $child->getNodeResult()->setResult($childResult);
                foreach ($this->hydrators as $hydrator){
                    if($hydrator->support($child)){
                        $hydrator->hydrate($child, $result);
                        $node->getNodeResult()->setResult($result);
                        break;
                    }
                }
            }
        }

    }

    /**
     * Check if the component is a composite and have association
     * @param Node $node
     * @return bool
     */
    private function isLastChild(Node $node): bool
    {
        if(!($node instanceof CompositeNode)){
            return true;
        }

        /** @var Node $child */
        foreach ($node->getChildren() as $child){
            $nodeResult = $child->getNodeResult();
            if(!$child->isScalar() && !$nodeResult?->isHydrated()){
                return false;
            }
        }

        return true;
    }

}