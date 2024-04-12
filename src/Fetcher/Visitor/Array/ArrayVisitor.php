<?php

namespace Verclam\SmartFetchBundle\Fetcher\Visitor\Array;

use Doctrine\ORM\QueryBuilder;
use Exception;
use Verclam\SmartFetchBundle\Attributes\SmartFetch;
use Verclam\SmartFetchBundle\Attributes\SmartFetchArray;
use Verclam\SmartFetchBundle\Fetcher\History\HistoryPaths;
use Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\Array\ArrayQueryBuilderGenerator;
use Verclam\SmartFetchBundle\Fetcher\ResultsProcessors\Array\ResultsProcessor;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Component;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Composite;
use Verclam\SmartFetchBundle\Fetcher\Visitor\SmartFetchVisitorInterface;

class ArrayVisitor implements SmartFetchVisitorInterface
{
    private HistoryPaths $history;

    /**
     * @param ArrayQueryBuilderGenerator $queryBuilder
     * @param ResultsProcessor $resultsProcessor
     */
    public function __construct(
        private readonly ArrayQueryBuilderGenerator     $queryBuilder,
        private readonly ResultsProcessor               $resultsProcessor,
    )
    {
        $this->initHistory();
    }

    private function initHistory(): void
    {
        $this->history = new HistoryPaths();
    }

    /**
     * @param Component $component
     * @return void
     */
    public function visit(Component $component): void
    {
        $component->handle($this);
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
    public function fetchResult(Component $component): void
    {
        //TODO: ADD MANAGEMENT OF THE MAX CONFIGURATION
        $queryBuilder = $this->generateQuery($component);

        $this->executeQueryBuilder($component, $queryBuilder);

        //If the node has parent, and have association children
        //So we must store the history, because we will visit his children
        //and the history will help us build the join query (Reverse to the root query)
        if($component->getParent() && $this->isRealComposite($component)) {
            $this->history->add($component->getParent());
        }
    }

    /**
     * @throws Exception
     */
    public function processResults(Component $component): void
    {
        $processedResult = [];

        if ($component instanceof Composite && $component->isCollection()) {
            $entities = $component->getResult();
            foreach ($entities as $singleEntity) {
                $arraySingleEntity = [$singleEntity];
                $processedResult = array_merge(
                    $processedResult,
                    $this->resultsProcessor->processResult($component, $arraySingleEntity)
                );
            }
        }else {
            $processedResult = $this->resultsProcessor->processResult($component);
        }

        $component->setResult($processedResult);

        // reset the history in case we will use this visitor in other places.
        $this->initHistory();
    }

    /**
     * Check if the component is a composite and have association
     * @param Component $component
     * @return bool
     */
    private function isRealComposite(Component $component): bool
    {
        if(!$component->isComposite()){
            return false;
        }

        foreach ($component->getChildren() as $child){
            if(!$child->isScalar()){
                return true;
            }
        }

        return false;
    }

    /**
     * Generate the full QB for having all the result for node
     * @param Component $component
     * @return QueryBuilder
     * @throws Exception
     */
    private function generateQuery(Component $component): QueryBuilder
    {
        return $this->queryBuilder->generate($component , $this->history);
    }

    /**
     * Fetch the result and set it in the node
     * @throws Exception
     */
    private function executeQueryBuilder(Component $component, QueryBuilder $queryBuilder): void
    {
        $result = match (!$component->isRoot() || ($component instanceof Composite && $component->isCollection())){
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

        $component->setResult($result);
    }
}