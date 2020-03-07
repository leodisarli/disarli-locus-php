<?php

namespace Locus;

use AwsServiceDiscovery\AwsServiceDiscovery;
use Mockery;
use PHPUnit\Framework\TestCase;
use Predis\Client;

class LocusTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @covers \Locus\Locus::__construct
     */
    public function testCreateLocus()
    {
        $locus = new Locus();
        $this->assertInstanceOf(Locus::class, $locus);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @covers \Locus\Locus::getUrl
     */
    public function testGetUrlItsOnEnv()
    {
        $namespace = 'namespace';
        $service = 'service';

        $locusClass = Mockery::mock(Locus::class)->makePartial();
        $locusClass->shouldReceive('getUrlFromEnv')
            ->with($service)
            ->once()
            ->andReturn('urlFromEnv');

        $response = $locusClass->getUrl($namespace, $service);

        $this->assertEquals($response, 'urlFromEnv');
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @covers \Locus\Locus::getUrl
     */
    public function testGetUrlItsOnCache()
    {
        $namespace = 'namespace';
        $service = 'service';
        $cache = [
            'urlFromCache1',
            'urlFromCache2',
        ];

        $locusClass = Mockery::mock(Locus::class)->makePartial();
        $locusClass->shouldReceive('getUrlFromEnv')
            ->with($service)
            ->once()
            ->andReturn(null);
        $locusClass->shouldReceive('getUrlsFromCache')
            ->with($service)
            ->once()
            ->andReturn($cache);
        $locusClass->shouldReceive('getRandomIndex')
            ->with($cache)
            ->once()
            ->andReturn(0);

        $response = $locusClass->getUrl($namespace, $service);

        $this->assertEquals($response, 'urlFromCache1');
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @covers \Locus\Locus::getUrl
     */
    public function testGetUrlItsOnServiceDiscovery()
    {
        $namespace = 'namespace';
        $service = 'service';
        $serviceDiscovery = [
            'urlFromServiceDiscovery1',
            'urlFromServiceDiscovery2',
        ];

        $locusClass = Mockery::mock(Locus::class)->makePartial();
        $locusClass->shouldReceive('getUrlFromEnv')
            ->with($service)
            ->once()
            ->andReturn(null);
        $locusClass->shouldReceive('getUrlsFromCache')
            ->with($service)
            ->once()
            ->andReturn(null);
        $locusClass->shouldReceive('getUrlsFromServiceDiscovery')
            ->with($namespace, $service)
            ->once()
            ->andReturn($serviceDiscovery);
        $locusClass->shouldReceive('getRandomIndex')
            ->with($serviceDiscovery)
            ->once()
            ->andReturn(0);

        $response = $locusClass->getUrl($namespace, $service);

        $this->assertEquals($response, 'urlFromServiceDiscovery1');
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @covers \Locus\Locus::getUrl
     */
    public function testGetUrlNoneItsFound()
    {
        $namespace = 'namespace';
        $service = 'service';

        $locusClass = Mockery::mock(Locus::class)->makePartial();
        $locusClass->shouldReceive('getUrlFromEnv')
            ->with($service)
            ->once()
            ->andReturn(null);
        $locusClass->shouldReceive('getUrlsFromCache')
            ->with($service)
            ->once()
            ->andReturn(null);
        $locusClass->shouldReceive('getUrlsFromServiceDiscovery')
            ->with($namespace, $service)
            ->once()
            ->andReturn(null);

        $response = $locusClass->getUrl($namespace, $service);

        $this->assertNull($response);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @covers \Locus\Locus::getRandomIndex
     */
    public function testGetRandomIndexOneIndex()
    {
        $urls = [
            'urlOne',
        ];
        $locus = new Locus();

        $response = $locus->getRandomIndex($urls);
        $this->assertEquals($response, 0);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @covers \Locus\Locus::getRandomIndex
     */
    public function testGetRandomIndexMultiple()
    {
        $urls = [
            'urlOne',
            'urlTwo',
            'urlThree',
        ];
        $locus = new Locus();

        $response = $locus->getRandomIndex($urls);
        $this->assertGreaterThanOrEqual(0, $response);
        $this->assertLessThanOrEqual(2, $response);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @covers \Locus\Locus::getUrlFromEnv
     */
    public function testGetUrlFromEnv()
    {
        $env = [
            'test' => [
                'url' => 'urlFromEnv'
            ],
        ];
        $locus = new Locus(
            [],
            $env
        );

        $response = $locus->getUrlFromEnv('test');
        $this->assertEquals($response, 'urlFromEnv');
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @covers \Locus\Locus::getUrlsFromCache
     * @covers \Locus\Locus::newRedis
     */
    public function testGetUrlFromCache()
    {
        $service = 'test';
        $cache = [
            'urlFromCache'
        ];

        Mockery::mock('overload:' . Client::class)
            ->shouldReceive('get')
            ->with("locus-{$service}")
            ->once()
            ->andReturn(json_encode($cache))
            ->getMock();
        $locus = new Locus();

        $response = $locus->getUrlsFromCache($service);
        $this->assertEquals($response, $cache);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @covers \Locus\Locus::setUrlToCache
     * @covers \Locus\Locus::newRedis
     */
    public function testSetUrlToCache()
    {
        $service = 'test';
        $urlList = [
            'urlToCache1',
            'urlToCache2',
        ];

        Mockery::mock('overload:' . Client::class)
            ->shouldReceive('set')
            ->with("locus-{$service}", json_encode($urlList))
            ->once()
            ->andReturn(true)
            ->getMock();
        $locus = new Locus();

        $response = $locus->setUrlToCache($service, $urlList);
        $this->assertTrue($response);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @covers \Locus\Locus::getUrlsFromServiceDiscovery
     * @covers \Locus\Locus::newServiceDiscovery
     */
    public function testGetUrlsFromServiceDiscoveryNotFound()
    {
        $namespace = 'namespace';
        $service = 'test';

        Mockery::mock('overload:' . AwsServiceDiscovery::class)
            ->shouldReceive('getServiceAllHealthUrl')
            ->with($namespace, $service)
            ->once()
            ->andReturn(null)
            ->getMock();
        $locus = new Locus();

        $response = $locus->getUrlsFromServiceDiscovery($namespace, $service);
        $this->assertNull($response);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @covers \Locus\Locus::getUrlsFromServiceDiscovery
     * @covers \Locus\Locus::newServiceDiscovery
     */
    public function testGetUrlsFromServiceDiscovery()
    {
        $namespace = 'namespace';
        $service = 'test';
        $urlList = [
            'urlFromServiceDiscovery1',
        ];

        Mockery::mock('overload:' . Client::class)
            ->shouldReceive('set')
            ->with("locus-{$service}", json_encode($urlList))
            ->once()
            ->andReturn(true)
            ->getMock();
        
        Mockery::mock('overload:' . AwsServiceDiscovery::class)
            ->shouldReceive('getServiceAllHealthUrl')
            ->with($namespace, $service)
            ->once()
            ->andReturn($urlList)
            ->getMock();
        $locus = new Locus();

        $response = $locus->getUrlsFromServiceDiscovery($namespace, $service);
        $this->assertEquals($response, $urlList);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @covers \Locus\Locus::clearCache
     * @covers \Locus\Locus::newRedis
     */
    public function testClearCache()
    {
        $service = 'test';

        Mockery::mock('overload:' . Client::class)
            ->shouldReceive('del')
            ->with("locus-{$service}")
            ->once()
            ->andReturn(true)
            ->getMock();
        $locus = new Locus();

        $response = $locus->clearCache($service);
        $this->assertTrue($response);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }
}
