<?php

namespace Folklore\Image\Tests\Unit;

use Folklore\Image\Tests\TestCase;
use Folklore\Image\Contracts\ImageHandler as ImageHandlerContract;
use Folklore\Image\Sources\LocalSource;
use Folklore\Image\Sources\FilesystemSource;
use Folklore\Image\SourceManager;
use Folklore\Image\Image;
use Folklore\Image\ImageHandler;

/**
 * @coversDefaultClass Folklore\Image\Image
 */
class ImageTest extends TestCase
{
    protected $image;

    protected function setUp(): void
    {
        parent::setUp();

        $this->image = app('image');
    }

    /**
     * Test the constructor
     *
     * @test
     * @covers ::__construct
     */
    public function testConstructor()
    {
        $image = new Image(app(), app('router'));
        $this->assertInstanceOf(Image::class, $image);
    }

    /**
     * Test source method without a name
     *
     * @test
     * @covers ::source
     */
    public function testSourceWithoutName()
    {
        $factory = $this->image->source();
        $this->assertInstanceOf(ImageHandlerContract::class, $factory);
        $this->assertInstanceOf(LocalSource::class, $factory->getSource());
    }

    /**
     * Test source method with a name
     *
     * @test
     * @covers ::source
     */
    public function testSourceWithName()
    {
        $factory = $this->image->source('filesystem');
        $this->assertInstanceOf(ImageHandlerContract::class, $factory);
        $this->assertInstanceOf(FilesystemSource::class, $factory->getSource());
    }

    /**
     * Test source method with an invalid name
     *
     * @test
     * @covers ::source
     */
    public function testSourceWithInvalidName()
    {
        $this->expectException(\Folklore\Image\Exception\InvalidSourceException::class);
        $factory = $this->image->source('invalid');
    }

    /**
     * Test that source method keeps factory instance
     *
     * @test
     * @covers ::source
     */
    public function testSourceKeepHandlerInstance()
    {
        $factory = $this->image->source();
        $factorySecond = $this->image->source();
        $this->assertTrue($factory === $factorySecond);
    }

    /**
     * Test that extend call the same method on source manager
     *
     * @test
     * @covers ::extend
     */
    public function testExtendCallSourceManager()
    {
        $driver = 'test';
        $callback = function () {
            return null;
        };

        $sourceManager = $this->getMockBuilder(SourceManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['extend'])
            ->getMock();

        $sourceManager->expects($this->once())
            ->method('extend')
            ->with($this->equalTo($driver), $this->equalTo($callback));

        app()->singleton('image.source', function () use ($sourceManager) {
            return $sourceManager;
        });

        $factory = $this->image->extend($driver, $callback);
    }

    /**
     * Test calling source
     *
     * @test
     * @covers ::extend
     * @covers ::__call
     */
    public function testCallingSource()
    {

        $imageHandler = $this->getMockBuilder(ImageHandler::class)
            ->disableOriginalConstructor()
            ->setMethods(['testMethod', 'setSource'])
            ->getMock();

        $imageHandler->expects($this->once())
            ->method('testMethod')
            ->with($this->equalTo('test'));

        $imageHandler->expects($this->once())
            ->method('setSource')
            ->with($this->image->getSourceManager()->driver());

        app()->bind(ImageHandlerContract::class, function () use ($imageHandler) {
            return $imageHandler;
        });

        $this->image->testMethod('test');
    }

    /**
     * Test the url method
     *
     * @test
     * @covers ::url
     */
    public function testUrl()
    {
        $urlGenerator = app('image.url');

        $path = 'medias/image.jpg';
        $config = [
            'width' => 300,
            'height' => 300,
            'rotate' => 90,
            'format' => '{dirname}/{filters}/{basename}.{extension}',
            'filters_format' => 'image/{filter}',
            'filter_format' => '{key}-{value}',
            'filter_separator' => '/'
        ];
        $this->assertEquals($this->image->url($path, $config), $urlGenerator->make($path, $config));
        $this->assertEquals($this->image->url($path, 300, 300, $config), $urlGenerator->make($path, 300, 300, $config));
    }

    /**
     * Test the pattern method
     *
     * @test
     * @covers ::pattern
     */
    public function testPattern()
    {
        $urlGenerator = app('image.url');

        $config = [
            'format' => '{dirname}/{filters}/{basename}.{extension}',
            'filters_format' => 'image/{filter}',
            'filter_format' => '{key}-{value}',
            'filter_separator' => '/'
        ];
        $this->assertEquals($this->image->pattern($config), $urlGenerator->pattern($config));
    }

    /**
     * Test the pattern method
     *
     * @test
     * @covers ::parse
     */
    public function testParse()
    {
        $urlGenerator = app('image.url');

        $url = '/uploads/image/300x300/rotate-90/negative/image.jpg';
        $config = [
            'format' => '{dirname}/{filters}/{basename}.{extension}',
            'filters_format' => 'image/{filter}',
            'filter_format' => '{key}-{value}',
            'filter_separator' => '/'
        ];
        $this->assertEquals($this->image->parse($url, $config), $urlGenerator->parse($url, $config));
    }

    /**
     * Test the routes method
     *
     * @test
     * @covers ::routes
     */
    public function testRoutes()
    {
        $this->image->routes(public_path('routes.php'));
        $this->assertTrue($this->app['router']->getRoutes()->hasNamedRoute('image.test'));

        $this->image->routes([
            'map' => public_path('routes.php'),
            'middleware' => ['test'],
        ]);
        $route = $this->app['router']->getRoutes()->getByName('image.test');
        $actions = $route->getAction();
        $this->assertArrayHasKey('middleware', $actions);
        $this->assertEquals(['test'], $actions['middleware']);
    }

    /**
     * Test the filter method
     *
     * @test
     * @covers ::filter
     * @covers ::hasFilter
     * @covers ::getFilter
     */
    public function testFilter()
    {
        $filter = [
            'width' => 70,
            'height' => 80,
            'crop' => true
        ];
        $this->image->filter('test', $filter);
        $this->assertTrue($this->image->hasFilter('test'));
        $this->assertEquals($filter, $this->image->getFilter('test'));
    }

    /**
     * Test the filter method
     *
     * @test
     * @covers ::getFilters
     * @covers ::setFilters
     */
    public function testGetFilters()
    {
        $filters = [
            'test' => [
                'width' => 70,
                'height' => 80,
                'crop' => true
            ]
        ];
        $this->image->setFilters($filters);
        $this->assertEquals($filters, $this->image->getFilters());
    }

    /**
     * Test the source manager
     *
     * @test
     * @covers ::getSourceManager
     */
    public function testGetSourceManager()
    {
        $this->assertEquals(app('image.source'), $this->image->getSourceManager());
    }

    /**
     * Test the imagine manager
     *
     * @test
     * @covers ::getImagineManager
     */
    public function testGetImagineManager()
    {
        $this->assertEquals(app('image.imagine'), $this->image->getImagineManager());
    }

    /**
     * Test an imagine instance
     *
     * @test
     * @covers ::getImagine
     */
    public function testGetImagine()
    {
        $this->assertEquals(app('image.imagine')->driver(), $this->image->getImagine());
    }

    /**
     * Test the url generator
     *
     * @test
     * @covers ::getUrlGenerator
     */
    public function testGetUrlGenerator()
    {
        $this->assertEquals(app('image.url'), $this->image->getUrlGenerator());
    }
}
