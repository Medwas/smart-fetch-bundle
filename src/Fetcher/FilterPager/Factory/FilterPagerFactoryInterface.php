<?php

namespace Verclam\SmartFetchBundle\Fetcher\FilterPager\Factory;

use Symfony\Component\HttpFoundation\Request;
use Verclam\SmartFetchBundle\Fetcher\FilterPager\DTO\AbstractFilterPagerDTO;

interface FilterPagerFactoryInterface
{
    public function support(string $className): bool;

    public function create(
        Request $request,
        string $className,
        array $denormalizeContext = [],
        array $deserializeContext = []
    ): ?AbstractFilterPagerDTO;

}