<?php

namespace Verclam\SmartFetchBundle\Event;

use Exception;
use ReflectionException;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Verclam\SmartFetchBundle\Attributes\SmartFetch;
use Verclam\SmartFetchBundle\Services\SmartFetchEntityFetcher;

class SmartFetchEventListener
{
    public function __construct(
        private readonly SmartFetchEntityFetcher $entityFetcher
    )
    {
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    public function __invoke(ControllerEvent $event): void
    {
        $controller = $event->getController();
        $request = $event->getRequest();

        if (!\is_array($controller) && method_exists($controller, '__invoke')) {
            $controller = [$controller, '__invoke'];
        }

        if (!\is_array($controller)) {
            return;
        }

        $className = \get_class($controller[0]);

        $object = new \ReflectionClass($className);
        $method = $object->getMethod($controller[1]);

        $methodAttributes = array_map(
            function (\ReflectionAttribute $attribute) {
                return $attribute->newInstance();
            },
            $method->getAttributes(SmartFetch::class, \ReflectionAttribute::IS_INSTANCEOF)
        );

        foreach ($methodAttributes as $attribute) {
            if (!$attribute->getClass()) {
                throw new \Error('When SmartFetch attribute used on a methode it must have a class name parameter.');
            }
            foreach ($this->entityFetcher->resolve($request, $attribute) as $entity) {
                $argumentName = $attribute->getArgumentName() ?? $attribute->getQueryName();
                $request->attributes->set($argumentName, $entity);
            }
        }
    }


}