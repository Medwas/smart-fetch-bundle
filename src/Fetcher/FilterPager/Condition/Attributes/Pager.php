<?php

namespace Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\Attributes;

use Attribute;

#[\Attribute(Attribute::TARGET_PROPERTY)]
class Pager extends AbstractCondition implements SmartFetchConditionInterface
{
    public int $rows;
    public int $page;

    public function __construct() {}

}