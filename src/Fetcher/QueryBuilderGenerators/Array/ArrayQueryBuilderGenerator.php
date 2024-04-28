<?php

namespace Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\Array;

use Doctrine\ORM\QueryBuilder;
use Exception;
use Verclam\SmartFetchBundle\Fetcher\History\HistoryPaths;
use Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\NodeQueryBuilderGeneratorInterface;
use Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\QueryBuilderGeneratorInterface;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\Node;

class ArrayQueryBuilderGenerator implements QueryBuilderGeneratorInterface
{

    /**
     * @param ArrayAddChildSelectQueryBuilderGenerator $addChildSelectQueryBuilderGenerator
     * @param iterable<NodeQueryBuilderGeneratorInterface> $queryBuilderGenerators
     */
    public function __construct(
        private readonly ArrayAddChildSelectQueryBuilderGenerator   $addChildSelectQueryBuilderGenerator,
        private readonly iterable                                   $queryBuilderGenerators
    )
    {
    }

    /**
     * @param Node $node
     * @param HistoryPaths $paths
     * @return QueryBuilder
     * @throws Exception
     */
    public function generate(Node $node, HistoryPaths $paths): QueryBuilder
    {
        $queryBuilder = null;

        foreach ($this->queryBuilderGenerators as $builderGenerator){
            if($builderGenerator->support($node)){
                $queryBuilder = $builderGenerator->generate($node, $paths);
                break;
            }
        }

        if(null === $queryBuilder){
            throw new Exception('No QueryBuilder Generator found !!!');
        }

        return $this->addChildSelectQueryBuilderGenerator
            ->generate($node, $queryBuilder);
    }

}