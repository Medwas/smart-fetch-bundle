<?php

namespace Services;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Verclam\SmartFetchBundle\Attributes\SmartFetch;
use Verclam\SmartFetchBundle\Services\ArgumentResolver;
use Verclam\SmartFetchBundle\Services\SmartFetchEntityFetcher;

class ArgumentResolverTest extends TestCase
{
    private MockObject $smartFetch;
    private MockObject $request;
    private MockObject $argumentMetadata;
    private MockObject $smartFetchEntityFetcher;
    private ArgumentResolver $argumentResolver;

    protected function setUp(): void
    {
        $this->smartFetch               = $this->createMock(SmartFetch::class);
        $this->request                  = $this->createMock(Request::class);
        $this->argumentMetadata         = $this->createMock(ArgumentMetadata::class);
        $this->smartFetchEntityFetcher  = $this->createMock(SmartFetchEntityFetcher::class);
        $this->argumentResolver         = new ArgumentResolver($this->smartFetchEntityFetcher);
    }

    public function testResolveShouldSuccess(): void
    {
        $options            = [$this->smartFetch];
        $expectedEntities   = [new \stdClass()];

        $this->argumentMetadata
            ->expects($this->once())
            ->method('getAttributes')
            ->with(SmartFetch::class, ArgumentMetadata::IS_INSTANCEOF)
            ->willReturn($options)
        ;

        $this->smartFetch->expects($this->once())->method('getClass')->willReturn('EntityClassName');
        $this->smartFetch->expects($this->once())->method('setClass')->with('EntityClassName');
        $this->smartFetchEntityFetcher->expects($this->once())->method('resolve')->with($this->request, $this->smartFetch)->willReturn($expectedEntities);

        $res = $this->argumentResolver->resolve($this->request, $this->argumentMetadata);

        $this->assertEquals($expectedEntities, $res);
    }

    public function testResolveShouldReturnEmptyArrayWhenAttributeNotValid(): void
    {
        $this->argumentMetadata
            ->expects($this->once())
            ->method('getAttributes')
            ->with(SmartFetch::class, ArgumentMetadata::IS_INSTANCEOF)
            ->willReturn([null])
        ;

        $this->smartFetch->expects($this->never())->method('getClass')->willReturn('EntityClassName');
        $this->smartFetch->expects($this->never())->method('setClass')->with('EntityClassName');
        $this->smartFetchEntityFetcher->expects($this->never())->method('resolve')->with($this->request, $this->smartFetch)->willReturn([]);

        $res = $this->argumentResolver->resolve($this->request, $this->argumentMetadata);

        $this->assertEquals([], $res);
    }

    public function testResolveShouldReturnEmptyArrayWhenClassNameNotFound(): void
    {
        $this->argumentMetadata
            ->expects($this->once())
            ->method('getAttributes')
            ->with(SmartFetch::class, ArgumentMetadata::IS_INSTANCEOF)
            ->willReturn([$this->smartFetch])
        ;

        $this->smartFetch->expects($this->once())->method('getClass')->willReturn(null);
        $this->argumentMetadata->expects($this->once())->method('getType')->willReturn(null);

        $this->smartFetch->expects($this->never())->method('setClass')->with('EntityClassName');
        $this->smartFetchEntityFetcher->expects($this->never())->method('resolve')->with($this->request, $this->smartFetch)->willReturn([]);

        $res = $this->argumentResolver->resolve($this->request, $this->argumentMetadata);

        $this->assertEquals([], $res);
    }
}