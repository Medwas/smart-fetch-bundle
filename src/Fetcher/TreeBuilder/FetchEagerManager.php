<?php

namespace Verclam\SmartFetchBundle\Fetcher\TreeBuilder;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Node\Node;

class FetchEagerManager
{
    public function __construct(
        private readonly SmartFetchObjectManager    $objectManager,
    )
    {
    }

    /**
     * Retrieve the entities that must be fetched eager.
     * Here we are looking for the entities that are not the owning side of the relation,
     * but are in ONE_TO_ONE relation, the getter will never work so we need to fetch eager it's relation.
     * Also, we are looking for the entities that are in ONE_TO_MANY relation and have subClasses, because
     * Doctrine be default will fetch it if we don't do it.
     * @param ClassMetadata $classMetadata
     * @return Node[]
     * @throws Exception
     */
    public function retrieveFetchEagerEntities(ClassMetadata $classMetadata): array
    {
        $fetchEagerChildren = [];

        foreach ($classMetadata->getAssociationMappings() as $insideAssociationMapping) {
            $insideClassMetadata = $this->objectManager
                ->getClassMetadata($insideAssociationMapping['targetEntity']);

            //https://github.com/doctrine/orm/issues/4389
            //https://github.com/doctrine/orm/issues/3778
            //https://github.com/doctrine/orm/issues/4389
            //vendor/doctrine/orm/lib/Doctrine/ORM/UnitOfWork.php:2968
            if ((($insideAssociationMapping['type'] === SmartFetchObjectManager::ONE_TO_ONE)
                    && !$insideAssociationMapping['isOwningSide']) ||
                (($insideAssociationMapping['type'] === SmartFetchObjectManager::ONE_TO_MANY) &&
                    count($insideClassMetadata->subClasses) > 0)
            ) {
                $fetchEagerChildren[] = [
                    'options'           => $insideAssociationMapping,
                    'classMetadata'     => $insideClassMetadata,
                ];
            }
        }

        return $fetchEagerChildren;
    }

    /**
     * @param ClassMetadata $classMetadata
     * @return array
     */
    public function retrieveSuccessorsClassMetadata(ClassMetadata $classMetadata): array
    {
        $successorsClassMetadata = [];

        foreach ($classMetadata->parentClasses as $parentClass) {
            $successorsClassMetadata[] = $this->objectManager->getClassMetadata($parentClass);
        }

        return $successorsClassMetadata;
    }

    /**
     * Verify if the node is fetch eager in case where
     * we have no possibility to return to the parent.
     * In other termes it means that in this entity there is no getter to return to the parent
     * so we need to fetch it eagerly directly from the parent.
     * @param Node $node
     * @param $options
     * @return void
     */
    public function hasLinkToParent(Node $node, $options): void
    {
        if(
            key_exists('mappedBy', $options) &&
            key_exists('inversedBy', $options) &&
            !$options['mappedBy'] &&
            !$options['inversedBy']
        ){
            $node->setFetchEager(true);
        }
    }

}