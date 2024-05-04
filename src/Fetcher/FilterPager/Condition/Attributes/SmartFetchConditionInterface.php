<?php

namespace Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\Attributes;

interface SmartFetchConditionInterface
{

    public function setJoined(bool $joined): void;
    public function isJoined(): bool;

}