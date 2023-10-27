<?php

    namespace Verclam\SmartFetchBundle\Fetcher\Condition\Attributes;

    use Attribute;

    #[\Attribute(Attribute::TARGET_PROPERTY)]
    class LeftJoin implements SmartFetchConditionInterface
    {

        public array $joins;
        public function __construct(string|array $join) {
            $this->joins = is_array($join) ? $join : [$join];
        }

    }