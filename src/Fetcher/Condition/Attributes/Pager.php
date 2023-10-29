<?php

    namespace Verclam\SmartFetchBundle\Fetcher\Condition\Attributes;

    use Attribute;

    #[\Attribute(Attribute::TARGET_PROPERTY)]
    class Pager implements SmartFetchConditionInterface
    {
        public const PAGE_METHOD_NAME = 'getPage';
        public const ROWS_METHOD_NAME = 'getRows';

        public function __construct() {}

    }