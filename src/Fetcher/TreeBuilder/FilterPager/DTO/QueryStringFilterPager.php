<?php

namespace Verclam\SmartFetchBundle\Fetcher\TreeBuilder\FilterPager\DTO;

use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\FilterPager\DTO\Enum\PayloadSourceEnum;

class QueryStringFilterPager extends AbstractFilterPagerDTO
{
    protected ?PayloadSourceEnum $payloadSource = PayloadSourceEnum::QUERY_STRING;

}