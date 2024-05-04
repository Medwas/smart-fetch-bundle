<?php

namespace Verclam\SmartFetchBundle\Fetcher\FilterPager\DTO\Enum;

enum PayloadSourceEnum: string
{
    case REQUEST_PAYLOAD = 'mapRequestPayload';
    case QUERY_STRING = 'mapQueryString';
}
