<?php

namespace Folklore\Image\Tests\Feature;

use Folklore\Image\Tests\TestCase;

/**
 *
 */
class RoutesTest extends TestCase
{
    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('image.routes', [
            // Path to the routes file that will be automatically mapped. Set to null
            // to prevent auto-loading of routes.
            'map' => null,

            // Default domain for routes
            'domain' => null,

            // Default namespace for controller
            'namespace' => null,

            // Default middlewares for routes
            'middleware' => [],

            // The controller serving the images
            'controller' => '\Folklore\Image\Http\ImageController@serve',

            // The name of the pattern that will be added to the Laravel Router.
            'pattern_name' => 'image_pattern',

            // The middleware used when a route as cache enabled
            'cache_middleware' => 'image.middleware.cache'
        ]);
    }

    /**
     * Test routes with specific filters
     *
     * @test
     */
    public function testRoutesFilters()
    {
        $this->app['router']->image('thumbnail/{pattern}', [
            'as' => 'image.thumbnail',
            'filters' => [
                'width' => 100,
                'height' => 100,
                'crop' => true,
            ],
        ]);

        $url = image()->url('image.jpg', [
            'route' => 'image.thumbnail'
        ]);
        $this->assertEquals('http://localhost/thumbnail/image.jpg', $url);

        $patterns = $this->app['router']->getPatterns();
        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression('/'.$patterns['image_pattern'].'/', 'image.jpg');
        } else {
            $this->assertRegExp('/'.$patterns['image_pattern'].'/', 'image.jpg');
        }

        $response = $this->call('GET', $url);
        $this->assertEquals($response->headers->get('Content-type'), 'image/jpeg');
    }
}
