<?php

namespace Verclam\SmartFetchBundle\Fetcher\FilterPager\DTO;

use Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\Attributes\Pager;
use Verclam\SmartFetchBundle\Fetcher\FilterPager\DTO\Enum\PayloadSourceEnum;
use Verclam\SmartFetchBundle\Fetcher\FilterPager\DTO\Interfaces\FilterPagerDTOInterface;

abstract class AbstractFilterPagerDTO implements FilterPagerDTOInterface
{
    protected ?PayloadSourceEnum $payloadSource = null;
    protected ?string $customSelectDQL = null;
    
    #[Pager]
    protected int $page;

    protected int $rows;

    public function getPage(): int
    {
        return $this->page;
    }

    public function setPage(int $page): void
    {
        $this->page = $page;
    }

    public function getRows(): int
    {
        return $this->rows;
    }

    public function setRows(int $rows): void
    {
        $this->rows = $rows;
    }

    public function getCustomSelectDQL(): ?string
    {
        return $this->customSelectDQL;
    }
}
