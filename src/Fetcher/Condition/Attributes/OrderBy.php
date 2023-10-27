<?php

    namespace Verclam\SmartFetchBundle\Fetcher\Condition\Attributes;

    use Attribute;

    #[\Attribute(Attribute::TARGET_PROPERTY)]
    class OrderBy implements SmartFetchConditionInterface
    {

        public function __construct(public string $property, public bool $joined = false) {
        }

    }