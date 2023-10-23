<?php

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Verclam\SmartFetchBundle\ArgumentResolver\SmartFetchArgumentResolver;
use Verclam\SmartFetchBundle\Services\ArgumentResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class SmartFetchArgumentResolverTest extends TestCase
{
    private MockObject $argumentResolver;
    private SmartFetchArgumentResolver $smartFetchArgumentResolver;
    private MockObject $request;
    private MockObject $argumentMetadata;

    protected function setUp(): void
    {
        $this->request                      = $this->createMock(Request::class);
        $this->argumentMetadata             = $this->createMock(ArgumentMetadata::class);
        $this->argumentResolver             = $this->createMock(ArgumentResolver::class);
        $this->smartFetchArgumentResolver   = new SmartFetchArgumentResolver($this->argumentResolver);
    }

    public function testResolveShouldSuccess(): void
    {
        $this->argumentResolver->expects($this->once())->method('resolve')->with($this->request, $this->argumentMetadata);

        $this->smartFetchArgumentResolver->resolve($this->request, $this->argumentMetadata);
    }
}