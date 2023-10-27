<?php

    namespace Verclam\SmartFetchBundle\Fetcher\Condition\Attributes;

    use Attribute;

    #[\Attribute(Attribute::TARGET_PROPERTY)]
    class Condition implements SmartFetchConditionInterface
    {
        public const NAME = 'FilterBy';
        public const EQUAL = ' = :';
        public const GREATER_THAN = ' > ';
        public const GREATER_THAN_OR_EQUAL = ' >= ';
        public const LESS_THAN = ' < ';
        public const LESS_THAN_OR_EQUAL = ' <= ';
        public const LIKE = ' LIKE :';
        public const NOT_EQUAL = '!';
        public const NOT = ' NOT ';

        public const IN = ' IN ';
        public const IS_NULL = ' IS NULL ';
        public const BETWEEN = ' BETWEEN ';
        public const CUSTOM_CONDITION = '';

        public const PROPERTY_NAME_PROPERTY = 'property';
        public const OPERATOR_PROPERTY = 'operator';

        public string $prefix;
        public string $suffix;

        public const DATA_TYPES_STRING = 'string';
        public const DATA_TYPES_DATE = 'date';

        public string $dataTypes = self::DATA_TYPES_STRING;
        public string $negation = ' ';
        public bool $joined = false;

        public function __construct(
            public string $property,
            public string $operator,
            array $options = [],
            public ?string $value = null,
        ) {
            match($this->operator){
                self::BETWEEN       => $this->handleBetweenOperator(),
                self::IN            => $this->handleInOperator(),
                self::LIKE          => $this->handleLikeOperator(),
                default             => $this->handleOthers(),
            };

            if(key_exists('negation', $options)){
                $this->negation = $options['negation'];
            }

            if(key_exists('dataTypes', $options)){
                $this->dataTypes = $options['dataTypes'];
            }

            if(key_exists('joined', $options)){
                $this->joined = $options['joined'];
            }
        }

        private function handleInOperator(): void
        {
            $this->prefix = '(:';
            $this->suffix = ')';
        }

        private function handleBetweenOperator(): void
        {
            $this->suffix = ' AND :';
            $this->prefix = ':';
        }

        private function handleLikeOperator(): void
        {
            $this->prefix = '%';
            $this->suffix = '%';
        }

        private function handleOthers(): void
        {
            $this->prefix = ':';
            $this->suffix = '';
        }

    }