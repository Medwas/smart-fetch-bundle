<?php

namespace Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\Utils;

use DateTime;
use Exception;

class DateUtils
{
    public static function getDate(?string $strDate): ?DateTime
    {
        try {
            if (empty($strDate)) {
                return null;
            }

            return new DateTime($strDate);
        } catch (Exception $e) {
            return null;
        }
    }
}
    
