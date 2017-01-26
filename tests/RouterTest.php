<?php

use Folklore\Image\Router;

/**
 * @coversDefaultClass Folklore\Image\Router
 */
class RouterTest extends ImageTestCase
{
    protected $router;

    public function setUp()
    {
        parent::setUp();

        $this->router = new Router(app('router'), app());
    }

    /**
     * Test getting the router
     * @test
     * @covers ::getRouter
     */
    public function testGetRouter()
    {
        $this->assertEquals(app('router'), $this->router->getRouter());
    }

    /**
     * Test add and get route
     * @test
     * @covers ::addRoute
     * @covers ::getRoute
     */
    public function testAddRoute()
    {
        $route = [
            'route' => '{pattern}'
        ];
        $this->router->addRoute($route, 'test');
        $this->assertEquals($route, $this->router->getRoute('test'));

        $this->router->addRoute($route);
        $this->assertEquals($route, $this->router->getRoute(1));
    }

    /**
     * Test add routes
     * @test
     * @covers ::addRoutes
     */
    public function testAddRoutes()
    {
        $routes = [
            'test' => [
                'route' => '{pattern}'
            ]
        ];
        $this->router->addRoutes($routes);
        $this->assertEquals($routes['test'], $this->router->getRoute('test'));
    }

    /**
     * Test getting route name
     * @test
     * @covers ::getRouteName
     */
    public function testGetRouteName()
    {
        $route = [
            'route' => '{pattern}'
        ];
        $this->router->addRoute($route, 'test');
        $this->assertEquals('image.test', $this->router->getRouteName('test'));

        $route = [
            'route' => '{pattern}',
            'as' => 'route.name'
        ];
        $this->router->addRoute($route, 'test_with_as');
        $this->assertEquals($route['as'], $this->router->getRouteName('test_with_as'));
    }

    /**
     * Test getting route name
     * @test
     * @covers ::registerRoutesOnRouter
     * @covers ::registerRouteOnRouter
     */
    public function testRegisterRouteOnRouter()
    {
        $routeConfig = [
            'as' => 'route.test',
            'route' => '{pattern}',
            'cache' => true,
            'domain' => 'example.com',
            'url' => [
                'format' => '{dirname}/{basename}{filters}.{extension}'
            ]
        ];
        $this->router->addRoute($routeConfig, 'test');
        $this->router->registerRoutesOnRouter();

        $route = app('router')->getRoutes()->getByName('route.test');
        $this->assertEquals('route.test', $route->getName());
        $this->assertEquals('{image_pattern_route_test}', $route->uri());
        $this->assertEquals([
            'image.middleware.cache'
        ], $route->middleware());
        $this->assertEquals($routeConfig['domain'], $route->domain());
        $this->assertEquals($routeConfig, $route->getAction()['image']);
        $this->assertEquals('\Folklore\Image\Http\ImageController@serve', $route->getAction()['uses']);
        $this->assertEquals(
            app('image.url')->pattern($routeConfig['url']),
            app('router')->getPatterns()['image_pattern_route_test']
        );
    }
}
