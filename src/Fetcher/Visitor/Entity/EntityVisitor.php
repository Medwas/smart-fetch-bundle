<?php

namespace Verclam\SmartFetchBundle\Fetcher\Visitor\Entity;

use Doctrine\ORM\QueryBuilder;
use Verclam\SmartFetchBundle\Attributes\SmartFetch;
use Verclam\SmartFetchBundle\Attributes\SmartFetchEntity;
use Verclam\SmartFetchBundle\Fetcher\Configuration\Configuration;
use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
use Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\Entity\EntityFetchEagerQueryBuilderGenerator;
use Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\Entity\EntityQueryBuilderGenerator;
use Verclam\SmartFetchBundle\Fetcher\ResultsProcessors\Entity\ResultsProcessor;
use Verclam\SmartFetchBundle\Fetcher\ResultsProcessors\NodeResultFactory;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\Node;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\CompositeNode;
use Verclam\SmartFetchBundle\Fetcher\Visitor\SmartFetchVisitorInterface;

class EntityVisitor implements SmartFetchVisitorInterface
{
    /**
     * @param Configuration                                $configuration
     * @param EntityQueryBuilderGenerator                  $queryBuilderGenerator
     */
    public function __construct(
        private readonly Configuration                          $configuration,
        private readonly EntityQueryBuilderGenerator            $queryBuilderGenerator,
        private readonly ResultsProcessor                       $resultsProcessor,
        private readonly NodeResultFactory                      $resultFactory,
    ) {
    }

    public function visit(Node $node): void
    {
        $node->handle($this);
    }

    public function support(SmartFetch $smartFetch): bool
    {
        return $smartFetch instanceof SmartFetchEntity;
    }

    /**
     * @throws \Exception
     */
    public function fetchResult(Node $node): void
    {
        if($node->isFetchEager()){
            return;
        }

        //TODO: ADD MANAGEMENT OF THE MAX CONFIGURATION
        $queryBuilder = $this->generateQuery($node);

        $this->executeQueryBuilder($node, $queryBuilder);
    }

    private function generateQuery(Node $node): QueryBuilder
    {
        return $this->queryBuilderGenerator->generate($node);
    }

    /**
     * @throws \Exception
     */
    private function executeQueryBuilder(Node $node, QueryBuilder $queryBuilder): void
    {
        $result = match (!$node->isRoot() || ($node instanceof CompositeNode && $node->isCollection())) {
            true       => $queryBuilder->getQuery()->getResult(),
            false      => $queryBuilder->getQuery()->getSingleResult(),
        };

        $nodeResult = $this->resultFactory->create(
            [
                'queryBuilder' => $queryBuilder,
                'result' => $result,
            ]
        );

        $node->setNodeResult($nodeResult);
    }

    private function fetchNonRoot(Node $node, QueryBuilder $queryBuilder): mixed
    {
        return match ($node->getRelationType()) {
            SmartFetchObjectManager::ONE_TO_MANY,
            SmartFetchObjectManager::ONE_TO_ONE => $queryBuilder->getQuery()->getOneOrNullResult(),
            SmartFetchObjectManager::MANY_TO_MANY,
            SmartFetchObjectManager::MANY_TO_ONE => $queryBuilder->getQuery()->getResult()
            };
    }

    /**
     * @throws \Exception
     */
    public function processResults(Node $node): void
    {
        // nothing to do here because entities are object and every is done in the fetch method, so we find
        // the final result by default in the root node
        $this->resultsProcessor->processResult($node);
    }
}
