<?php

    namespace Verclam\SmartFetchBundle\Fetcher\TreeBuilder;

    use Exception;
    use Verclam\SmartFetchBundle\Fetcher\Condition\Attributes\Condition;
    use Doctrine\Persistence\Mapping\ClassMetadata;
    use Verclam\SmartFetchBundle\Attributes\SmartFetch;
    use Verclam\SmartFetchBundle\Fetcher\Condition\ConditionFactory;
    use Verclam\SmartFetchBundle\Fetcher\ObjectManager\SmartFetchObjectManager;
    use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Component;
    use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Composite;
    use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Leaf;

    class ComponentFactory
    {
        public const LEAF = 'leaf';
        public const COMPOSITE = 'composite';
        public const ROOT = 'root';

        public function __construct(
            private readonly ConditionFactory $conditionFactory,
        )
        {
        }

        /**
         * @throws Exception
         */
        public function generate(ClassMetadata $classMetadata, SmartFetch $smartFetch, string $type, array $options = []): Component
        {
            return match ($type) {
                self::LEAF      => $this->generateLeaf($classMetadata, $options),
                self::COMPOSITE => $this->generateComposite($classMetadata, $options),
                self::ROOT      => $this->generateRoot($classMetadata, $smartFetch),
                default         => throw new Exception('Unknown type')
            };
        }

        public function generateLeaf(ClassMetadata $classMetadata, array $options): Leaf
        {
            $leaf = new Leaf();
            $leaf->setClassMetadata($classMetadata);
            $leaf->setAlias($this->generateCommonAliases($options['fieldName']));
            $leaf->setPropertyName($options['fieldName']);
            $leaf->setPropertyInformations($options);
            return $leaf;
        }

        public function generateComposite(ClassMetadata $classMetadata, array $options): Composite
        {
            $composite = new Composite();
            $composite->setClassMetadata($classMetadata);
            $composite->setAlias($this->generateCommonAliases($options['fieldName']));
            $composite->setPropertyName($options['fieldName']);
            $composite->setPropertyInformations($options);
            return $composite;
        }

        /**
         * @throws Exception
         */
        public function generateRoot(ClassMetadata $classMetadata, SmartFetch $smartFetch): Composite
        {
            $root      = new Composite(true);
            $rootAlias = $this->generateRootAlias($smartFetch);

            //TODO: detect if the queryName is a property if not get the identificator
            $condition = $this->conditionFactory->generate(
                [
                    'type'      => ConditionFactory::FILTER_BY,
                    'property'  => $smartFetch->getQueryName(),
                    'operator'  => Condition::EQUAL,
                    'value'     => $smartFetch->getQueryValue()
                ]
            );

            $root->setClassMetadata($classMetadata);
            $root->setAlias($rootAlias);
            $root->setPropertyName($rootAlias);
            $root->setPropertyInformations(['type' => SmartFetchObjectManager::ONE_TO_ONE]);
            return $root->addCondition($condition);
        }

        private function generateRootAlias(SmartFetch $smartFetch): string
        {
            $entityNameParts    = explode('\\', $smartFetch->getClass());
            $entityName         = end($entityNameParts);
            return strtolower($entityName);
        }

        private function generateCommonAliases(string $propertyName): string
        {
            //TODO: remove this line, and let it only for debug purpose;
            return $propertyName . '_a' . rand(0, 1000);
            return $propertyName[0] . $propertyName[-1] . '_a' . rand(0, 1000);
        }
    }