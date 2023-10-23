<?php

namespace EventListener;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Verclam\SmartFetchBundle\Attributes\SmartFetch;
use Verclam\SmartFetchBundle\EventListener\SmartFetchEventListener;
use Verclam\SmartFetchBundle\Services\SmartFetchEntityFetcher;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class SmartFetchEventListenerTest extends TestCase
{
    private MockObject $request;
    private MockObject $kernel;
    private MockObject $smartFetchEntityFetcher;
    private MockObject $attributes;
    private SmartFetchEventListener $smartFetchEventListener;

    protected function setUp(): void
    {
        $this->request                  = $this->createMock(Request::class);
        $this->attributes               = $this->createMock(ParameterBag::class);
        $this->request->attributes      = $this->attributes;
        $this->kernel                   = $this->createMock(HttpKernelInterface::class);
        $this->smartFetchEntityFetcher  = $this->createMock(SmartFetchEntityFetcher::class);
        $this->smartFetchEventListener  = new SmartFetchEventListener($this->smartFetchEntityFetcher);
    }

    /**
     * @throws \ReflectionException
     */
    public function testListenerShouldSuccess(): void
    {
        $attribute  = new SmartFetch('id', 'Entity', ['field', 'field.subField'], 'user');
        $entity     = new \stdClass();
        $controller = new class {
            #[SmartFetch('id', 'Entity', ['field', 'field.subField'], 'user')]
            public function mockAction($user)
            {}
        };

        $controllerEvent = new ControllerEvent($this->kernel, [new $controller(), 'mockAction'], $this->request, null);

        $this->smartFetchEntityFetcher->expects($this->once())->method('resolve')->with($this->request, $attribute)->willReturn([$entity]);
        $this->attributes->expects($this->once())->method('set')->with('user', $entity);

        $this->smartFetchEventListener->__invoke($controllerEvent);
    }

    /**
     * @throws \ReflectionException
     */
    public function testListenerShouldDoNothingWhenControllerCallableNotValid(): void
    {
        $attribute  = new SmartFetch('id', 'Entity', ['field', 'field.subField'], 'user');
        $entity     = new \stdClass();
        $controller = new class {
            #[SmartFetch('id', 'Entity', ['field', 'field.subField'], 'user')]
            public function mockAction($user)
            {}
        };

        $controllerEvent = new ControllerEvent($this->kernel, fn() => 1, $this->request, null);

        $this->smartFetchEntityFetcher->expects($this->never())->method('resolve')->with($this->request, $attribute)->willReturn([$entity]);
        $this->attributes->expects($this->never())->method('set')->with('user', $entity);

        $this->smartFetchEventListener->__invoke($controllerEvent);
    }

    /**
     * @throws \ReflectionException
     */
    public function testListenerShouldFailWhenNoClassProvided(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('When SmartFetch attribute used on a methode it must have a class name parameter.');

        $attribute  = new SmartFetch('id', null, ['field', 'field.subField'], 'user');
        $entity     = new \stdClass();
        $controller = new class {
            #[SmartFetch('id', null, ['field', 'field.subField'], 'user')]
            public function mockAction($user)
            {}
        };

        $controllerEvent = new ControllerEvent($this->kernel, [new $controller(), 'mockAction'], $this->request, null);

        $this->smartFetchEntityFetcher->expects($this->never())->method('resolve')->with($this->request, $attribute)->willReturn([$entity]);
        $this->attributes->expects($this->never())->method('set')->with('user', $entity);

        $this->smartFetchEventListener->__invoke($controllerEvent);
    }
}