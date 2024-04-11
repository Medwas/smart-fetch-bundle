<?php

namespace Verclam\SmartFetchBundle\ArgumentResolver;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Verclam\SmartFetchBundle\Services\ArgumentResolver;

/**
 * After Symfony 6.2, this ValueResolver will be called in order to resolve the
 * argument (parameters) in the controller
 */
class SmartFetchArgumentResolver implements ValueResolverInterface
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
}