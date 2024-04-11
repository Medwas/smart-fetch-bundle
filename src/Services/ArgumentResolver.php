<?php

    namespace Verclam\SmartFetchBundle\Services;

    use Exception;
    use Verclam\SmartFetchBundle\Attributes\SmartFetch;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
    use Verclam\SmartFetchBundle\Attributes\SmartFetchInterface;

    /**
     * Confirm that the attribute coming from the parameter
     * is a SmartFetch and Fetch the entitie(s).
     */
    class ArgumentResolver
    {
        public function __construct(
            private readonly SmartFetchEntityFetcher $entityFetcher,
        )
        {
        }

        /**
         * @throws Exception
         */
        public function resolve(Request $request, ArgumentMetadata $argument): iterable
        {
            $options = $argument->getAttributes(SmartFetchInterface::class, ArgumentMetadata::IS_INSTANCEOF);
            $options = $options[0] ?? null;

            if (!($options instanceof SmartFetchInterface)) {
                return [];
            }

            $className = $options->getClass() ?? $argument->getType();

            if (!$className) {
                return [];
            }

            $options->setClass($className);
            $options->setArgumentName($argument->getName());

            return $this->entityFetcher->resolve($request, $options);
        }

    }