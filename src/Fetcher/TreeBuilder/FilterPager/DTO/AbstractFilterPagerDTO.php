<?php

namespace Verclam\SmartFetchBundle\Fetcher\TreeBuilder\FilterPager\DTO;

use Verclam\SmartFetchBundle\Fetcher\Condition\Attributes\Pager;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\FilterPager\DTO\Enum\PayloadSourceEnum;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\FilterPager\DTO\Interfaces\FilterPagerDTOInterface;

abstract class AbstractFilterPagerDTO implements FilterPagerDTOInterface
{
    protected ?PayloadSourceEnum $payloadSource = null;
    protected ?string $customSelectDQL = null;
    #[Pager]
    private int $page;

    private int $rows;

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
