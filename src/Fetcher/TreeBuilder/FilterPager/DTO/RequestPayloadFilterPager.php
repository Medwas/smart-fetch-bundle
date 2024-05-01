<?php

namespace Verclam\SmartFetchBundle\Fetcher\TreeBuilder\FilterPager\DTO;

use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\FilterPager\DTO\Enum\PayloadSourceEnum;

class RequestPayloadFilterPager extends AbstractFilterPagerDTO
{
    protected ?PayloadSourceEnum $payloadSource = PayloadSourceEnum::REQUEST_PAYLOAD;

}