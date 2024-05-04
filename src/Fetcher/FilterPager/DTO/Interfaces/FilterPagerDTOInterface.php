<?php

namespace Verclam\SmartFetchBundle\Fetcher\FilterPager\DTO\Interfaces;

interface FilterPagerDTOInterface
{
    public function getPage(): int;
    public function setPage(int $page): void;
    public function getRows():int;
    public function setRows(int $rows): void;
    public function getCustomSelectDQL(): ?string;
}
