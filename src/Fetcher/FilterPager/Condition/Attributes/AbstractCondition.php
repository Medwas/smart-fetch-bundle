<?php

namespace Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\Attributes;

class AbstractCondition
{
    private bool $joined = false;
    public function setJoined(bool $joined):void{
        $this->joined = $joined;
    }
    public function isJoined(): bool{
        return $this->joined;
    }
}