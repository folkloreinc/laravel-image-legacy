<?php namespace Folklore\Image\Tests;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Folklore\Image\Exception\FormatException;
use Orchestra\Testbench\TestCase;

class ImageTestCase extends TestCase
{
    protected $imagePath = '/image.jpg';
    protected $imageSmallPath = '/image_small.jpg';
    protected $imageSize;
    protected $imageSmallSize;

    public function setUp()
    {
        parent::setUp();
        
        $this->image = $this->app['image'];
        $this->imageSize = getimagesize(public_path().$this->imagePath);
        $this->imageSmallSize = getimagesize(public_path().$this->imageSmallPath);
    }

    public function tearDown()
    {
        $customPath = $this->app['path.public'].'/custom';
        $this->app['config']->set('image.write_path', $customPath);
        
        $this->image->deleteManipulated($this->imagePath);
        
        parent::tearDown();
    }

    public function testURLisValid()
    {

        $patterns = array(
            array(
                'url_parameter' => null
            ),
            array(
                'url_parameter' => '-image({options})',
                'url_parameter_separator' => '-'
            ),
            array(
                'url_parameter' => '-i-{options}',
                'url_parameter_separator' => '-'
            ),
            array(
                'url_parameter' => '/i/{options}',
                'url_parameter_separator' => '/'
            )
        );

        foreach ($patterns as $pattern) {
            $options = array(
                'grayscale',
                'crop' => true,
                'colorize' => 'FFCC00'
            );
            $url = $this->image->url($this->imagePath, 300, 300, array_merge($pattern, $options));

            //Check against pattern
            $urlMatch = preg_match('#'.$this->image->pattern($pattern['url_parameter']).'#', $url, $matches);
            $this->assertEquals($urlMatch, 1);

            //Check path
            $parsedPath = $this->image->parse($url, $pattern);
            $this->assertEquals($parsedPath['path'], $this->imagePath);

            //Check options
            foreach ($options as $key => $value) {
                if (is_numeric($key)) {
                    $this->assertTrue($parsedPath['options'][$value]);
                } else {
                    $this->assertEquals($parsedPath['options'][$key], $value);
                }
            }

        }
    }
    
    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app->instance('path.public', __DIR__.'/fixture');
    }

    protected function getPackageProviders($app)
    {
        return array('Folklore\Image\ImageServiceProvider');
    }

    protected function getPackageAliases($app)
    {
        return array(
            'Image' => 'Folklore\Image\Facades\Image'
        );
    }
}
