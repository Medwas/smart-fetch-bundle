<?php

namespace Verclam\SmartFetchBundle\ArgumentValueResolver;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Verclam\SmartFetchBundle\Attributes\SmartFetch;
use Verclam\SmartFetchBundle\Attributes\SmartFetchInterface;
use Verclam\SmartFetchBundle\Services\ArgumentResolver;

/**
 * Before Symfony 6.2, this ArgumentValueResolver will be called in order to resolve the
 * argument (parameters) in the controller
 */
class SmartFetchArgumentValueResolver implements ArgumentValueResolverInterface
{

    public function __construct(
        private readonly ArgumentResolver $resolver,
    )
    {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        return $this->resolver->resolve($request, $argument);
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        $options = $argument->getAttributes(SmartFetch::class, ArgumentMetadata::IS_INSTANCEOF);
        $options = $options[0] ?? null;

        return $options instanceof SmartFetch;
    }

}