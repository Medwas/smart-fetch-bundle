<?php

    namespace Verclam\SmartFetchBundle\Services;

    use Verclam\SmartFetchBundle\Attributes\SmartFetch;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

    class ArgumentResolver
    {
        public function __construct(
            private readonly SmartFetchEntityFetcher $entityFetcher,
        )
        {
        }

        public function resolve(Request $request, ArgumentMetadata $argument): iterable
        {
            $options = $argument->getAttributes(SmartFetch::class, ArgumentMetadata::IS_INSTANCEOF);
            $options = $options[0] ?? null;

            if (!($options instanceof SmartFetch)) {
                return [];
            }

            $className = $options->getClass() ?? $argument->getType();

            if (!$className) {
                return [];
            }

            $options->setClass($className);

            return $this->entityFetcher->resolve($request, $options);
        }

    }