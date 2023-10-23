<?php

namespace ArgumentValueResolver;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Verclam\SmartFetchBundle\ArgumentValueResolver\SmartFetchArgumentValueResolver;
use Verclam\SmartFetchBundle\Attributes\SmartFetch;
use Verclam\SmartFetchBundle\Services\ArgumentResolver;

class SmartFetchArgumentValueResolverTest extends TestCase
{
    private MockObject $argumentResolver;
    private MockObject $smartFetch;
    private MockObject $request;
    private MockObject $argumentMetadata;
    private SmartFetchArgumentValueResolver $smartFetchArgumentValueResolver;

    protected function setUp(): void
    {
        $this->smartFetch                   = $this->createMock(SmartFetch::class);
        $this->request                      = $this->createMock(Request::class);
        $this->argumentMetadata             = $this->createMock(ArgumentMetadata::class);
        $this->argumentResolver             = $this->createMock(ArgumentResolver::class);
        $this->smartFetchArgumentValueResolver  = new SmartFetchArgumentValueResolver($this->argumentResolver);
    }

    public function testResolveShouldSuccess(): void
    {
        $this->argumentResolver->expects($this->once())->method('resolve')->with($this->request, $this->argumentMetadata);

        $this->smartFetchArgumentValueResolver->resolve($this->request, $this->argumentMetadata);
    }

    public function testSupportShouldSuccess(): void
    {
        $this->argumentMetadata
            ->expects($this->once())
            ->method('getAttributes')
            ->with(SmartFetch::class, ArgumentMetadata::IS_INSTANCEOF)
            ->willReturn([$this->smartFetch])
        ;

        $bool = $this->smartFetchArgumentValueResolver->supports($this->request, $this->argumentMetadata);
        $this->assertTrue($bool);
    }

    public function testSupportShouldFail(): void
    {
        $this->argumentMetadata
            ->expects($this->once())
            ->method('getAttributes')
            ->with(SmartFetch::class, ArgumentMetadata::IS_INSTANCEOF)
            ->willReturn([new \stdClass()])
        ;

        $bool = $this->smartFetchArgumentValueResolver->supports($this->request, $this->argumentMetadata);
        $this->assertFalse($bool);
    }
}