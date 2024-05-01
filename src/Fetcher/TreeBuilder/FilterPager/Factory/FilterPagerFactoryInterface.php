<?php

namespace Verclam\SmartFetchBundle\Fetcher\TreeBuilder\FilterPager\Factory;

use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\FilterPager\DTO\AbstractFilterPagerDTO;
use Symfony\Component\HttpFoundation\Request;

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