<?php

namespace Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\Attributes;

use Attribute;

#[\Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class OrderBy extends AbstractCondition implements SmartFetchConditionInterface
{

    public function __construct(
        public string $fieldName,
        public ?string $value = null,
    )
    {
    }

}