<?php namespace Folklore\Image\Tests;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Folklore\Image\Exception\FormatException;
use Orchestra\Testbench\TestCase;

class ImageServeTestCase extends TestCase
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

    public function testServeWriteImage()
    {
        $this->app['config']->set('image.write_image', true);

        $url = $this->image->url($this->imagePath, 300, 300, [
            'crop' => true
        ]);

        $response = $this->call('GET', $url);

        $this->assertTrue($response->isOk());

        $imagePath = $this->app['path.public'].'/'.basename($url);
        $this->assertFileExists($imagePath);

        $sizeManipulated = getimagesize($imagePath);
        $this->assertEquals($sizeManipulated[0], 300);
        $this->assertEquals($sizeManipulated[1], 300);

        $this->app['config']->set('image.write_image', false);
    }

    public function testServeWriteImagePath()
    {
        $customPath = 'custom';

        $this->app['config']->set('image.write_image', true);
        $this->app['config']->set('image.write_path', $customPath);

        $url = $this->image->url($this->imagePath, 300, 300, [
            'crop' => true
        ]);

        $response = $this->call('GET', $url);

        $this->assertTrue($response->isOk());

        $imagePath = public_path($customPath.'/'.basename($url));
        $this->assertFileExists($imagePath);

        $sizeManipulated = getimagesize($imagePath);
        $this->assertEquals($sizeManipulated[0], 300);
        $this->assertEquals($sizeManipulated[1], 300);

        $this->app['config']->set('image.write_image', false);
        $this->app['config']->set('image.write_path', null);
    }

    public function testServeNoResize()
    {

        $url = $this->image->url($this->imagePath, null, null, array(
            'grayscale'
        ));
        $response = $this->call('GET', $url);

        $this->assertTrue($response->isOk());

        $sizeManipulated = getimagesizefromstring($response->getContent());
        $this->assertEquals($sizeManipulated[0], $this->imageSize[0]);
        $this->assertEquals($sizeManipulated[1], $this->imageSize[1]);
    }

    public function testServeResizeWidth()
    {
        $url = $this->image->url($this->imagePath, 300);
        $response = $this->call('GET', $url);

        $this->assertTrue($response->isOk());

        $sizeManipulated = getimagesizefromstring($response->getContent());
        $this->assertEquals($sizeManipulated[0], 300);
    }

    public function testServeResizeHeight()
    {
        $url = $this->image->url($this->imagePath, null, 300);
        $response = $this->call('GET', $url);

        $this->assertTrue($response->isOk());

        $sizeManipulated = getimagesizefromstring($response->getContent());
        $this->assertEquals($sizeManipulated[1], 300);
    }

    public function testServeResizeCrop()
    {
        //Both height and width with crop
        $url = $this->image->url($this->imagePath, 300, 300, array(
            'crop' => true
        ));
        $response = $this->call('GET', $url);

        $this->assertTrue($response->isOk());

        $sizeManipulated = getimagesizefromstring($response->getContent());
        $this->assertEquals($sizeManipulated[0], 300);
        $this->assertEquals($sizeManipulated[1], 300);
    }
    
    public function testServeResizeCropSmall()
    {
        //Both height and width with crop
        $url = $this->image->url($this->imageSmallPath, 300, 300, array(
            'crop' => true
        ));
        $response = $this->call('GET', $url);

        $this->assertTrue($response->isOk());

        $sizeManipulated = getimagesizefromstring($response->getContent());
        $this->assertEquals($sizeManipulated[0], 300);
        $this->assertEquals($sizeManipulated[1], 300);
    }

    public function testServeWrongParameter()
    {
        $url = $this->image->url($this->imagePath, 300, 300, array(
            'crop' => true,
            'wrong' => true
        ));
        
        try {
            $response = $this->call('GET', $url);
            $this->assertSame(404, $response->getStatusCode());
        }
        catch(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e)
        {
            $this->assertInstanceOf('\Symfony\Component\HttpKernel\Exception\NotFoundHttpException', $e);
        }
    }

    public function testServeWrongFile()
    {
        $url = $this->image->url('/wrong123.jpg', 300, 300, array(
            'crop' => true,
            'wrong' => true
        ));
        
        try {
            $response = $this->call('GET', $url);
            $this->assertSame(404, $response->getStatusCode());
        }
        catch(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e)
        {
            $this->assertInstanceOf('\Symfony\Component\HttpKernel\Exception\NotFoundHttpException', $e);
        }
    }

    public function testServeWrongFormat()
    {
        $url = $this->image->url('/wrong.jpg', 300, 300, array(
            'crop' => true
        ));
        
        try {
            $response = $this->call('GET', $url);
            $this->assertSame(500, $response->getStatusCode());
        }
        catch(\Symfony\Component\HttpKernel\Exception\HttpException $e)
        {
            $this->assertInstanceOf('\Symfony\Component\HttpKernel\Exception\HttpException', $e);
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
