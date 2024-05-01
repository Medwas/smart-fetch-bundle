<?php

namespace Verclam\SmartFetchBundle\Fetcher\TreeBuilder\FilterPager\Factory;

use Symfony\Component\HttpFoundation\Request;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\FilterPager\DTO\AbstractFilterPagerDTO;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\FilterPager\DTO\RequestPayloadFilterPager;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class RequestPayloadFilterPagerFactory implements FilterPagerFactoryInterface
{
    public function __construct(
        private readonly SerializerInterface&DenormalizerInterface  $serializer,
    )
    {
    }

    public function support(string $className): bool
    {
        return is_a($className, RequestPayloadFilterPager::class, true);
    }

    public function create(
        Request $request,
        string $className,
        array $denormalizeContext = [],
        array $deserializeContext = []
    ): ?AbstractFilterPagerDTO
    {
        if (null === $format = $request->getContentTypeFormat()) {
            throw new HttpException(Response::HTTP_UNSUPPORTED_MEDIA_TYPE, 'Unsupported format.');
        }

        if ($data = $request->request->all()) {
            return $this->serializer->denormalize($data, $className, null, $denormalizeContext);
        }

        if ('' === $data = $request->getContent()) {
            return null;
        }

        if ('form' === $format) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'Request payload contains invalid "form" data.');
        }

        try {
            return $this->serializer->deserialize($data, $className, $format, $deserializeContext);
        } catch (UnsupportedFormatException $e) {
            throw new HttpException(Response::HTTP_UNSUPPORTED_MEDIA_TYPE, sprintf('Unsupported format: "%s".', $format), $e);
        } catch (NotEncodableValueException $e) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, sprintf('Request payload contains invalid "%s" data.', $format), $e);
        }
    }
}