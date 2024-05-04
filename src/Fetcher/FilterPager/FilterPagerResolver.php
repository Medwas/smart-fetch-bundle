<?php

namespace Verclam\SmartFetchBundle\Fetcher\FilterPager;

use Error;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Verclam\SmartFetchBundle\Attributes\SmartFetch;
use Verclam\SmartFetchBundle\Fetcher\FilterPager\DTO\AbstractFilterPagerDTO;
use Verclam\SmartFetchBundle\Fetcher\FilterPager\Factory\FilterPagerFactoryInterface;

class FilterPagerResolver
{

    /**
     * @see \Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT
     * @see DenormalizerInterface::COLLECT_DENORMALIZATION_ERRORS
     */
    private const CONTEXT_DENORMALIZE = [
        'disable_type_enforcement' => true,
        'collect_denormalization_errors' => true,
    ];

    /**
     * @see DenormalizerInterface::COLLECT_DENORMALIZATION_ERRORS
     */
    private const CONTEXT_DESERIALIZE = [
        'collect_denormalization_errors' => true,
    ];

    /**
     * @param iterable<FilterPagerFactoryInterface> $filterPagerFactories
     */
    public function __construct(
        private readonly iterable   $filterPagerFactories,
    )
    {
    }

    public function resolve(Request $request, SmartFetch $smartFetch): ?AbstractFilterPagerDTO
    {
        if(null === $filterPagerClassname = $smartFetch->filterPagerClass){
            return null;
        }

        if (!is_a($filterPagerClassname, AbstractFilterPagerDTO::class, true)) {
            throw new Error(
                sprintf(
                    'FilterPagerClass must be a %s but got %s',
                    AbstractFilterPagerDTO::class,
                    $filterPagerClassname
                )
            );
        }

        if(!class_exists($filterPagerClassname)){
            throw new Error(
                sprintf(
                    'FilterPagerClass %s does not exist',
                    $filterPagerClassname
                )
            );
        }

        return $this->create($request, $filterPagerClassname);
    }

    private function create(Request $request, string $filterPagerClassname): AbstractFilterPagerDTO
    {
        $filterPagerDTO = null;

        foreach ($this->filterPagerFactories as $filterPagerFactory) {
            if ($filterPagerFactory->support($filterPagerClassname)) {
                $filterPagerDTO = $filterPagerFactory->create(
                    $request,
                    $filterPagerClassname,
                    self::CONTEXT_DENORMALIZE,
                    self::CONTEXT_DESERIALIZE
                );
            }
        }

        if(null === $filterPagerDTO){
            throw new Error(
                sprintf(
                    'The provided %s className is not supported by any of the registered FilterPagerFactories.',
                    $filterPagerClassname
                )
            );
        }

        return $filterPagerDTO;
    }

}