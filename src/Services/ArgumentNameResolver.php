<?php

namespace Verclam\SmartFetchBundle\Services;

use LogicException;
use ReflectionParameter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Verclam\SmartFetchBundle\Attributes\SmartFetch;

/**
 * Its role is to automatically guess and connect the
 * queryName with the argumentName
 */
class ArgumentNameResolver
{

    /**
     * @param SmartFetch $smartFetch
     * @param Request $request
     * @param array<ReflectionParameter>|null $reflexionParams
     * @return void
     */
    public function resolve(
        SmartFetch $smartFetch,
        Request $request,
        ?array $reflexionParams
    ): void
    {
        $queryName = $smartFetch->getQueryName();
        $argumentName = $smartFetch->getArgumentName();

        if($argumentName && $queryName){
            return;
        }

        $routeParams = $this->getQueryParameters($request);

        match ($reflexionParams){
            null => $this->resolveFromArgumentName($smartFetch, $routeParams),
            default => $this->resolveBoth($smartFetch, $routeParams, $reflexionParams, $request)
        };
    }

    /**
     * @param SmartFetch $smartFetch
     * @param array $routeParams
     * @return void
     */
    private function resolveFromArgumentName(
        SmartFetch $smartFetch,
        array $routeParams,
    ): void {

        if(count($routeParams) === 1){
            $smartFetch->setQueryName(
                array_key_first($routeParams)
            );
            return;
        }

        $argumentName = $smartFetch->getArgumentName();

        if(null === $argumentName){
            throw new LogicException(
                'ArgumentName not found, did you forget to specify it ?'
            );
        }

        if(key_exists($argumentName, $routeParams)){
            $smartFetch->setQueryName($argumentName);
            return;
        }

        throw new LogicException(
            'Could not resolve the queryName, did you forget to add in the SmartFetch attribute.'
        );
    }

    /**
     * @param SmartFetch $smartFetch
     * @param array $routeParams
     * @param array<ReflectionParameter> $reflexionParams
     * @return void
     */
    private function resolveBoth(
        SmartFetch $smartFetch,
        array $routeParams,
        array $reflexionParams,
        Request $request,
    ): void
    {
        $attributes = $request->attributes;

        $routeParams = array_filter(
            array_keys($routeParams),
            function (string $routeParam) use($attributes){
                return !is_object($attributes->get($routeParam)) && !is_array($attributes->get($routeParam));
            }
        );

        $routeParamsParamsLength = count($routeParams);
        $reflexionParamsLength = count($reflexionParams);

        if(!$routeParamsParamsLength || !$reflexionParamsLength){
            throw new LogicException(
                'Not enough route params, or controller params.'
            );
        }

        if($routeParamsParamsLength === 1 && $reflexionParamsLength === 1){
            $smartFetch->setQueryName(
                $routeParams[array_key_first($routeParams)]
            );
            $smartFetch->setArgumentName(
                $reflexionParams[0]->name
            );
            return;
        }

        $sameClassParams = array_filter($reflexionParams,
            function (ReflectionParameter $reflectionParameter) use ($smartFetch, $attributes) {
                return $reflectionParameter->getType()->getName() === $smartFetch->getType()
                    && !is_object($attributes->get($reflectionParameter->name))
                    && !is_array($attributes->get($reflectionParameter->name));
            }
        );

        $sameClassParamsLength = count($sameClassParams);

        if(!$sameClassParamsLength){
            throw new LogicException(
                'Could not resolve the parameters, please provide the correct class.'
            );
        }

        if($routeParamsParamsLength === 1 && $sameClassParamsLength === 1){
            $smartFetch->setQueryName(
                $routeParams[array_key_first($routeParams)]
            );
            $smartFetch->setArgumentName(
                $sameClassParams[0]->name
            );
            return;
        }

        $nameIntersection = array_uintersect_assoc(
            $sameClassParams,
            $routeParams,
            function (ReflectionParameter $reflectionParameter, $routeParam) use ($attributes) {

                if(is_object($attributes->get($reflectionParameter->name)) ||
                is_array($attributes->get($reflectionParameter->name))){
                    return -1;
                }

                if($reflectionParameter->name === $routeParam){
                    return 0;
                }

                return $reflectionParameter->name > $routeParam ?  1 : -1;
            }
        );

        if(count($nameIntersection) === 1){
            $smartFetch->setArgumentName(
                $nameIntersection[array_key_first($nameIntersection)]->name
            );
            $smartFetch->setQueryName(
                $nameIntersection[array_key_first($nameIntersection)]->name
            );
            return;
        }

        throw new LogicException('Could not resolve the argument name');
    }

    /**
     * @param Request $request
     * @return array
     */
    private function getQueryParameters(Request $request): array
    {
        return $request->attributes->get('_route_params');
    }

}