<?php

    namespace Verclam\SmartFetchBundle\Services;

    use Exception;
    use LogicException;
    use Verclam\SmartFetchBundle\Attributes\SmartFetch;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

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
            $attribute = $argument->getAttributes(SmartFetch::class, ArgumentMetadata::IS_INSTANCEOF);
            $attribute = $attribute[0] ?? null;

            if (!($attribute instanceof SmartFetch)) {
                return [];
            }

            $className = $attribute->getClass() ?? $argument->getType();

            if (!$className) {
                return [];
            }

            if(!class_exists($className)){
                throw new LogicException(
                    sprintf(
                        'The provided class "%s" does\'t exits', $className
                    )
                );
            }

            $attribute->setClass($className);
            $attribute->setArgumentName($argument->getName());

            return $this->entityFetcher->resolve($request, $attribute);
        }

    }