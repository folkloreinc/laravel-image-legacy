<?php

namespace Folklore\Image\Tests\Unit;

use Folklore\Image\Tests\TestCase;
use Folklore\Image\RouteRegistrar;
use Folklore\Image\Sources\LocalSource;
use Folklore\Image\Sources\FilesystemSource;

/**
 * @coversDefaultClass Folklore\Image\RouteRegistrar
 */
class RouteRegistrarTest extends TestCase
{
    protected $registrar;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registrar = new RouteRegistrar(app('router'), app('image.url'));
        $this->registrar->setPatternName(config('image.routes.pattern_name'));
        $this->registrar->setCacheMiddleware(config('image.routes.cache_middleware'));
        $this->registrar->setController(config('image.routes.controller'));
    }

    /**
     * Test adding a normal route
     * @test
     * @covers ::image
     */
    public function testAddNormalRoute()
    {
        $this->registrar->image('{pattern}', [
            'as' => 'image.test'
        ]);

        $route = app('router')->getRoutes()->getByName('image.test');
        $action = $route->getAction();
        $this->assertEquals($action['as'], 'image.test');
        $this->assertEquals($action['uses'], config('image.routes.controller'));
        $this->assertEquals($action['middleware'], []);
        $this->assertEquals($action['image'], []);
        $this->assertEquals($route->methods(), ['GET', 'HEAD']);
        $this->assertEquals($route->uri(), '{'.config('image.routes.pattern_name').'}');
    }

    /**
     * Test adding a route with config
     * @test
     * @covers ::image
     */
    public function testAddRouteWithConfig()
    {
        $this->registrar->image('{pattern}', [
            'as' => 'image.test',
            'allow_size' => true,
            'allow_filters' => false,
        ]);

        $route = app('router')->getRoutes()->getByName('image.test');
        $action = $route->getAction();
        $this->assertEquals($action['image'], [
            'allow_size' => true,
            'allow_filters' => false,
        ]);
    }

    /**
     * Test adding a route with cache
     * @test
     * @covers ::image
     */
    public function testAddRouteWithCache()
    {
        $this->registrar->image('{pattern}', [
            'as' => 'image.test',
            'cache' => true,
        ]);

        $route = app('router')->getRoutes()->getByName('image.test');
        $action = $route->getAction();
        $this->assertEquals($action['middleware'], [
            config('image.routes.cache_middleware')
        ]);
    }

    /**
     * Test adding a route with url config
     * @test
     * @covers ::image
     */
    public function testAddRouteWithUrlConfig()
    {
        $urlConfig = [
            'format' => '{dirname}/{basename}{filters}.{extension}',
            'filters_format' => '-image({filter})',
            'filter_format' => '{key}({value})',
            'filter_separator' => '-'
        ];
        $this->registrar->image('{pattern}', [
            'as' => 'image.test',
            'pattern' => $urlConfig,
        ]);

        $route = app('router')->getRoutes()->getByName('image.test');
        $this->assertEquals($route->uri(), '{image_pattern_test}');
        $patterns = app('router')->getPatterns();
        $this->assertEquals($patterns['image_pattern_test'], app('image.url')->pattern($urlConfig));
    }

    /**
     * Test setting/getting pattern name
     * @test
     * @covers ::getPatternName
     * @covers ::setPatternName
     */
    public function testPatternName()
    {
        $this->registrar->setPatternName('test');
        $this->assertEquals('test', $this->registrar->getPatternName());
    }

    /**
     * Test setting/getting cache middleware
     * @test
     * @covers ::getCacheMiddleware
     * @covers ::setCacheMiddleware
     */
    public function testCacheMiddleware()
    {
        $this->registrar->setCacheMiddleware('test');
        $this->assertEquals('test', $this->registrar->getCacheMiddleware());
    }

    /**
     * Test setting/getting controller
     * @test
     * @covers ::getController
     * @covers ::setController
     */
    public function testController()
    {
        $this->registrar->setController('test');
        $this->assertEquals('test', $this->registrar->getController());
    }
}
