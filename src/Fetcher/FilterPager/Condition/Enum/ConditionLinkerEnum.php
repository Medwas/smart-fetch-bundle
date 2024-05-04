<?php

namespace Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\Enum;

enum ConditionLinkerEnum: string
{
    case ANDWHERE = 'andWhere';
    case ORWHERE = 'orWhere';

}
