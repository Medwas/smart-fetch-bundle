<?php

    namespace Verclam\SmartFetchBundle\ArgumentResolver;

    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
    use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
    use Verclam\SmartFetchBundle\Services\ArgumentResolver;

    class SmartFetchArgumentResolver implements ValueResolverInterface
    {

        public function __construct(
            private readonly ArgumentResolver   $resolver,
        )
        {
        }

        public function resolve(Request $request, ArgumentMetadata $argument): iterable
        {
            return $this->resolver->resolve($request, $argument);
        }

    }