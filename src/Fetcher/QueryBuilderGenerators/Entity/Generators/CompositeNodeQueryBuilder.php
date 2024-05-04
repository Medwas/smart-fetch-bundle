<?php

namespace Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\Entity\Generators;

use Doctrine\ORM\QueryBuilder;
use Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\Attributes\FilterBy;
use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
use Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\Entity\EntityReverseQueryBuilderGenerator;
use Verclam\SmartFetchBundle\Fetcher\QueryBuilderGenerators\NodeQueryBuilderGeneratorInterface;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\Node;

class CompositeNodeQueryBuilder implements NodeQueryBuilderGeneratorInterface
{

    public function __construct(
        private readonly SmartFetchObjectManager    $objectManager,
    )
    {
    }

    public function generate(Node $node): QueryBuilder
    {
        $parentNode = $node->getParentNode();

        $queryBuilder = $this->objectManager->createQueryBuilder()
            ->select($node->getAlias())
            ->from($node->getClassName(), $node->getAlias());

        $this->addJoin($node, $queryBuilder);

        //in case it the root, we don't need to make any parent identifier condition
        if(!$node->isRoot()){
            $this->addParentIdentifiersCondition($parentNode, $queryBuilder);
        }

        return $queryBuilder;
    }

    /**
     * @param Node $childNode
     * @param QueryBuilder $queryBuilder
     * @return void
     */
    private function addJoin(Node $childNode, QueryBuilder $queryBuilder): void
    {
        $associationClassname = $childNode
            ->getClassMetadata()
            ->getAssociationTargetClass(
                $childNode->getParentProperty()
            );

        $parentNode = $childNode->getParentNode();
        $nodeClassName = $parentNode->getClassName();

        if($associationClassname !== $nodeClassName){
            $this->addInheritanceJoin($childNode, $queryBuilder);
            return;
        }

        $queryBuilder->leftJoin(
            $childNode->getAlias() . '.' . $childNode->getParentProperty(),
            $parentNode->getAlias()
        );
    }

    private function addInheritanceJoin(
        Node $childNode,
        QueryBuilder $queryBuilder
    ): void
    {
        $parentNode = $childNode->getParentNode();

        if(!$parentNode->isSuccessorEntity()){
            return;
        }

        $parentClassname = $childNode
            ->getClassMetadata()
            ->getAssociationTargetClass(
                $childNode->getParentProperty()
            );

        foreach ($parentNode->getInheritedClassMetadata() as $inheritedClassMetadata){

            if($parentClassname !== $inheritedClassMetadata->getName()){
                continue;
            }

            $childClassMetadata = $parentNode->getClassMetadata();
            $childClassname = $childClassMetadata->getName();
            $childPropertyName = $childNode->getParentProperty();
            $childAlias = $childPropertyName[0] . $childPropertyName[-1] . '_a' . rand(0, PHP_INT_MAX);
            //TODO: Manage composte identifier
            $childIdentifier = $childClassMetadata->getIdentifier()[0];
            $parentIdentifier = $inheritedClassMetadata->getIdentifier()[0];

            $queryBuilder->leftJoin(
                $childNode->getAlias() . '.' . $childNode->getParentProperty(),
                $childAlias
            );

            $queryBuilder->innerJoin(
                $childClassname,
                $parentNode->getAlias(),
                'WITH',
                $parentNode->getAlias() . '.' . $childIdentifier .
                ' = ' . $childAlias . '.' . $parentIdentifier
            );

            break;
        }

    }

    /**
     * @param Node $node
     * @param QueryBuilder $queryBuilder
     * @return void
     */
    /**
     * Create the condition using the parent result to optimise the request to the DB
     * @param Node $parentNode
     * @param QueryBuilder $queryBuilder
     * @return void
     */
    private function addParentIdentifiersCondition(Node $parentNode, QueryBuilder $queryBuilder): void
    {
        $parentNodeResult = $parentNode->getNodeResult();

        if(null === $parentNodeResult){
            throw new Exception('At this point the parent node result should never be null!!');
        }

        $results = $parentNodeResult->getResult();

        $queryBuilder->andWhere(
            $parentNode->getAlias() . ' IN(:identifiers)'
        );

        $queryBuilder->setParameter('identifiers', $results);
    }

    public function support(Node $node): bool
    {
        return !$node->isRoot();
    }
}