<?php

namespace Verclam\SmartFetchBundle\Fetcher\FilterPager\DTO;

use Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\Attributes\Pager;
use Verclam\SmartFetchBundle\Fetcher\FilterPager\DTO\Enum\PayloadSourceEnum;

class QueryStringFilterPager extends AbstractFilterPagerDTO
{
    protected ?PayloadSourceEnum $payloadSource = PayloadSourceEnum::QUERY_STRING;

    #[Pager]
    protected int $page;

    protected int $rows;

}