<?php

namespace Verclam\SmartFetchBundle\Fetcher\FilterPager\DTO;

use Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\Attributes\Pager;
use Verclam\SmartFetchBundle\Fetcher\FilterPager\DTO\Enum\PayloadSourceEnum;

class RequestPayloadFilterPager extends AbstractFilterPagerDTO
{
    protected ?PayloadSourceEnum $payloadSource = PayloadSourceEnum::REQUEST_PAYLOAD;

    #[Pager]
    protected int $page;

    protected int $rows;
}