<?php

namespace Verclam\SmartFetchBundle\Fetcher\FilterPager\Factory;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Verclam\SmartFetchBundle\Fetcher\FilterPager\DTO\AbstractFilterPagerDTO;
use Verclam\SmartFetchBundle\Fetcher\FilterPager\DTO\QueryStringFilterPager;

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