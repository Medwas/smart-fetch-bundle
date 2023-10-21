<?php

    namespace Verclam\SmartFetchBundle\Attributes;

    use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

    #[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_METHOD)]
    class SmartFetch extends ParamConverter
    {

        private $name;

        /**
         * The parameter class.
         *
         * @var string
         */
        private $class;


        /**
         * Whether or not the parameter is optional.
         *
         * @var bool
         */
        private $isOptional = false;

        /**
         * Use explicitly named converter instead of iterating by priorities.
         *
         * @var string
         */
        private $converter;

        private $joinEntities = [];

        private $argumentName;

        private $entityManager;


        public function __construct(
            string $queryName,
            string $class = null,
            array $joinEntities = [],
            string $argumentName = null,
            string $entityManager = null
        ) {
            $values['value'] = $queryName;
            $values['argumentName'] = $argumentName;
            $values['joinEntities'] = $joinEntities;
            $values['class']        = $class;
            $values['isOptional']   = false;
            $values['converter']    = 'verclam_smart_fetch_param_converter';
            $values['entityManager'] = $entityManager;
            parent::__construct($values);
        }



        /**
         * Returns the parameter name.
         *
         * @return string
         */
        public function getName()
        {
            return $this->name;
        }

        /**
         * Sets the parameter name.
         *
         * @param string $name The parameter name
         */
        public function setValue($name)
        {
            $this->setName($name);
        }

        /**
         * Sets the parameter name.
         *
         * @param string $name The parameter name
         */
        public function setName($name)
        {
            $this->name = $name;
        }

        /**
         * Returns the parameter class name.
         *
         * @return string
         */
        public function getClass()
        {
            return $this->class;
        }

        /**
         * Sets the parameter class name.
         *
         * @param string $class The parameter class name
         */
        public function setClass($class)
        {
            $this->class = $class;
        }

        /**
         * Sets whether or not the parameter is optional.
         *
         * @param bool $optional Whether the parameter is optional
         */
        public function setIsOptional($optional)
        {
            $this->isOptional = (bool) $optional;
        }

        /**
         * Returns whether or not the parameter is optional.
         *
         * @return bool
         */
        public function isOptional()
        {
            return $this->isOptional;
        }

        /**
         * Get explicit converter name.
         *
         * @return string
         */
        public function getConverter()
        {
            return $this->converter;
        }

        /**
         * Set explicit converter name.
         *
         * @param string $converter
         */
        public function setConverter($converter)
        {
            $this->converter = $converter;
        }

        /**
         * Returns the annotation alias name.
         *
         * @return string
         *
         * @see ConfigurationInterface
         */
        public function getAliasName()
        {
            return 'converters';
        }

        /**
         * Multiple ParamConverters are allowed.
         *
         * @return bool
         *
         * @see ConfigurationInterface
         */
        public function allowArray()
        {
            return true;
        }

        /**
         * @return mixed
         */
        public function getArgumentName()
        {
            return $this->argumentName;
        }

        /**
         * @param mixed $argumentName
         */
        public function setArgumentName($argumentName): void
        {
            $this->argumentName = $argumentName;
        }

        public function getJoinEntities(): array
        {
            return $this->joinEntities;
        }

        public function setJoinEntities(array $joinEntities): void
        {
            $this->joinEntities = $joinEntities;
        }

        /**
         * @return mixed
         */
        public function getEntityManager()
        {
            return $this->entityManager;
        }

        /**
         * @param mixed $entityManager
         */
        public function setEntityManager($entityManager): void
        {
            $this->entityManager = $entityManager;
        }
    }