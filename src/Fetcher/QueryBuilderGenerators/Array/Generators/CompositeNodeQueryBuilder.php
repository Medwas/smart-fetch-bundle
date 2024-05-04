<?php

namespace Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\Array\Generators;

use Doctrine\ORM\QueryBuilder;
use Exception;
use Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\Attributes\FilterBy;
use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
use Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\NodeQueryBuilderGeneratorInterface;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\Node;

class CompositeNodeQueryBuilder implements NodeQueryBuilderGeneratorInterface
{
    public function __construct(
        private readonly SmartFetchObjectManager    $objectManager,
    )
    {
    }

    /**
     * @param Node $node
     * @return QueryBuilder
     * @throws Exception
     * @throws Exception
     */
    public function generate(Node $node): QueryBuilder
    {
        $parentNode = $node->getParentNode();

        $queryBuilder = $this->objectManager->createQueryBuilder()
            ->select($this->buildScalarSelect($node))
            ->from($parentNode->getClassName(), $parentNode->getAlias());

        $this->addSelect($node, $queryBuilder);
        $this->addJoin($node, $queryBuilder);

        //in case it the root, we don't need to make any parent identifier condition
        if(!$node->isRoot()){
            $this->addParentIdentifiersCondition($parentNode, $queryBuilder);
        }

        return $queryBuilder;
    }

    /**
     * @param Node $node
     * @param QueryBuilder $queryBuilder
     * @return void
     * @throws Exception
     */
    private function addSelect(Node $node, QueryBuilder $queryBuilder): void
    {
        if($node->getParentNode()->isRoot() && !$node->getParentNode()->isCollection()){
            return;
        }

        $parent = $node->getParentNode();

        $identifierProperty = $parent->getClassMetadata()->getIdentifier();

        if(count($identifierProperty) > 1){
            throw new Exception(
                'Composite keys are not supported, Doctrine\'s best practice, says that it is better to avoid using it'
            );
        }

        $identifierProperty = $identifierProperty[0];

        $identifierField = $parent->getAlias() . '.' . $identifierProperty;
        $identifierAlias = $parent->getAlias() . '_' . $identifierProperty;

        $queryBuilder->addSelect("$identifierField as $identifierAlias");
    }

    /**
     * @param Node $node
     * @param QueryBuilder $queryBuilder
     * @return void
     */
    private function addJoin(Node $node, QueryBuilder $queryBuilder): void
    {
        $parent = $node->getParentNode();

        $queryBuilder->leftJoin($parent->getAlias() . '.' . $node->getFieldName(),
            $node->getAlias());
    }

    /**
     * @param Node $parentNode
     * @param QueryBuilder $queryBuilder
     * @return void
     */

    /**
     * Create the condition using the parent's identifier to optimise the request to the DB
     * @param Node $parentNode
     * @param QueryBuilder $queryBuilder
     * @return void
     * @throws Exception
     */
    private function addParentIdentifiersCondition(Node $parentNode, QueryBuilder $queryBuilder): void
    {
        if($parentNode->isRoot() && !$parentNode->isCollection()){
            return;
        }
        
        $identifierNode = $parentNode->getIdentifierNode();

        if(null === $identifierNode){
            throw new Exception('At this point the identifier node should never be null!!');
        }

        $identifierNodeResult = $identifierNode->getNodeResult();

        if(null === $identifierNodeResult){
            throw new Exception('At this point the identifier node result should never be null!!');
        }

        $identifiers = $identifierNodeResult->getResult();

        $queryBuilder->andWhere(
            $parentNode->getAlias() . '.' . $identifierNode->getFieldName() . ' IN(:identifiers)'
        );

        $queryBuilder->setParameter('identifiers', $identifiers);
    }

    private function buildScalarSelect(Node $node): string
    {
        $alias = $node->getAlias();
        $selector = '';

        /** @var Node $child */
        foreach ($node->getChildren() as $child){
            if(!$child->isScalar()){
                continue;
            }

            if(strlen($selector) > 0){
                $selector .= ', ';
            }

            $selector .= $alias . '.' . $child->getFieldName();
        }

        return $selector;
    }

    public function support(Node $node): bool
    {
        return !$node->isRoot();
    }
}