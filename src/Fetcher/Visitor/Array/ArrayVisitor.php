<?php

namespace Verclam\SmartFetchBundle\Fetcher\Visitor\Array;

use Doctrine\ORM\QueryBuilder;
use Exception;
use Verclam\SmartFetchBundle\Attributes\SmartFetch;
use Verclam\SmartFetchBundle\Attributes\SmartFetchArray;
use Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\Array\ArrayQueryBuilderGenerator;
use Verclam\SmartFetchBundle\Fetcher\ResultsProcessors\Array\ResultsProcessor;
use Verclam\SmartFetchBundle\Fetcher\ResultsProcessors\NodeResultFactory;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\Node;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\CompositeNode;
use Verclam\SmartFetchBundle\Fetcher\Visitor\SmartFetchVisitorInterface;

class ArrayVisitor implements SmartFetchVisitorInterface
{

    /**
     * @param ArrayQueryBuilderGenerator $queryBuilder
     * @param ResultsProcessor $resultsProcessor
     */
    public function __construct(
        private readonly ArrayQueryBuilderGenerator     $queryBuilder,
        private readonly ResultsProcessor               $resultsProcessor,
        private readonly NodeResultFactory                  $resultFactory,
    )
    {
    }


    /**
     * @param Node $node
     * @return void
     */
    public function visit(Node $node): void
    {
        $node->handle($this);
    }

    /**
     * @param SmartFetch $smartFetch
     * @return bool
     */
    public function support(SmartFetch $smartFetch): bool
    {
        return $smartFetch instanceof SmartFetchArray;
    }

    /**
     * @throws Exception
     */
    public function fetchResult(Node $node): void
    {
        if(!($node instanceof CompositeNode) && $node->isFetchEager()){
            return;
        }

        //TODO: ADD MANAGEMENT OF THE MAX CONFIGURATION
        $queryBuilder = $this->generateQuery($node);

        $this->executeQueryBuilder($node, $queryBuilder);
        $this->generateIdentifiers($node);
    }

    /**
     * @throws Exception
     */
    public function processResults(Node $node): void
    {
        $processedResult = [];

        $nodeResult = $node->getNodeResult();

        if ($node instanceof CompositeNode && $node->isCollection()) {
            $entities = $nodeResult->getResult();

            foreach ($entities as $singleEntity) {
                $arraySingleEntity = [$singleEntity];


                $this->resultsProcessor->processResult($node, $arraySingleEntity);
                $processedResult = array_merge(
                    $processedResult,
                    $arraySingleEntity
                );
            }
        }else {
            $processedResult = $nodeResult->getResult();
            $this->resultsProcessor->processResult($node, $processedResult);
        }

        $nodeResult->setResult($processedResult);
    }

    /**
     * Check if the node is a composite and have association
     * @param Node $node
     * @return bool
     */
    private function isRealComposite(Node $node): bool
    {
        if(!$node->isComposite()){
            return false;
        }

        foreach ($node->getChildren() as $child){
            if(!$child->isScalar()){
                return true;
            }
        }

        return false;
    }

    /**
     * Generate the full QB for having all the result for node
     * @param Node $node
     * @return QueryBuilder
     * @throws Exception
     */
    private function generateQuery(Node $node): QueryBuilder
    {
        return $this->queryBuilder->generate($node);
    }

    /**
     * Fetch the result and set it in the node
     * @throws Exception
     */
    private function executeQueryBuilder(Node $node, QueryBuilder $queryBuilder): void
    {
        $result = match (!$node->isRoot() || ($node instanceof CompositeNode && $node->isCollection())){
            true       => $queryBuilder->getQuery()->getArrayResult(),
            false      => $queryBuilder->getQuery()->getSingleResult(),
        };

        // In case when we have a single result and every field is null
        // that means no result, so we do it manually to an empty array
        // we will need to investigate to understand why in some cases
        // no result give an array with null values.
        if(count($result) === 1){
            $allFieldsAreNull = true;
            foreach ($result[0] as $property){
                if(!is_null($property)){
                    $allFieldsAreNull = false;
                    break;
                }
            }
            if(true === $allFieldsAreNull){
                $result = [];
            }
        }

        $nodeResult = $this->resultFactory->create(
            [
                'queryBuilder' => $queryBuilder,
                'result' => $result,
            ]
        );

        $node->setNodeResult($nodeResult);
    }


    /**
     * Create the identifiers result of the current that will serve to create optimised
     * query builder for the children using an IN(identifiers) condition to avoid too many joins
     * @param Node $node
     * @return void
     */
    public function generateIdentifiers(Node $node): void
    {
        $nodeResult = $node->getNodeResult();
        $arrayResult = $nodeResult->getResult();

        if($node->isRoot() && !$node->isCollection()){
            return;
        }

        $identifierNode = $node->getIdentifierNode();
        $identifierPropertyName = $identifierNode->getFieldName();

        $identifiers = array_unique(
            array_column($arrayResult, $identifierPropertyName)
        );

        $identifierNodeResult = $this->resultFactory->create(
            [
                'result' => $identifiers,
            ]
        );

        $identifierNode->setNodeResult($identifierNodeResult);
    }
}