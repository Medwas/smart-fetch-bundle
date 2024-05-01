<?php

namespace Verclam\SmartFetchBundle\Fetcher\TreeBuilder\FilterPager\Factory;

use Symfony\Component\HttpFoundation\Request;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\FilterPager\DTO\AbstractFilterPagerDTO;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\FilterPager\DTO\QueryStringFilterPager;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class QueryStringFilterPagerFactory implements FilterPagerFactoryInterface
{

    public function __construct(
        private readonly SerializerInterface&DenormalizerInterface  $serializer,
    )
    {
    }

    public function support(string $className): bool
    {
        return is_a($className, QueryStringFilterPager::class, true);
    }

    public function create(
        Request $request,
        string $className,
        array $denormalizeContext = [],
        array $deserializeContext = []
    ): ?AbstractFilterPagerDTO
    {
        if (!$data = $request->query->all()) {
            return null;
        }

        return $this->serializer->denormalize($data, $className, null, $denormalizeContext);
    }
}