<?php

namespace Folklore\Image\Tests\Unit\Http;

use Folklore\Image\Tests\TestCase;
use Folklore\Image\Http\CacheMiddleware;

/**
 * @coversDefaultClass Folklore\Image\Http\CacheMiddleware
 */
class CacheMiddlewareTest extends TestCase
{
    protected $middleware;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $path = app('image')->url('image.jpg', [
            'width' => 100,
            'height' => 100
        ]);
        $cachePath = public_path('cache/'.ltrim($path, '/'));
        if (file_exists($cachePath)) {
            unlink($cachePath);
        }

        parent::tearDown();
    }

    /**
     * Test handle request
     * @test
     * @covers ::handle
     */
    public function testHandle()
    {
        app('router')->image('{pattern}', [
            'as' => 'image.test',
            'source' => 'local',
            'cache' => true,
            'cache_path' => public_path('cache')
        ]);

        $path = app('image')->url('image.jpg', [
            'width' => 100,
            'height' => 100
        ]);

        $response = $this->call('GET', $path);
        $this->assertEquals(200, $response->status());
        $this->assertTrue(file_exists(public_path('cache/'.ltrim($path, '/'))));

        // Do a second request to check if it's the same file
        $secondResponse = $this->call('GET', $path);
        $this->assertEquals(200, $secondResponse->status());
        $this->assertEquals($response->getContent(), $secondResponse->getContent());
    }
}
