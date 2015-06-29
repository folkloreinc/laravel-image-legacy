<?php

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Folklore\Image\Exception\FormatException;
use Orchestra\Testbench\TestCase;

class ImageTestCase extends TestCase {

    protected $imagePath = '/image.jpg';
    protected $imageSmallPath = '/image_small.jpg';
    protected $imageSize;
    protected $imageSmallSize;

    public function setUp()
    {
        parent::setUp();
        
        $this->app->instance('path.public', __DIR__.'/fixture');

        Image::deleteManipulated($this->imagePath);

        $this->imageSize = getimagesize(public_path().$this->imagePath);
        $this->imageSmallSize = getimagesize(public_path().$this->imageSmallPath);
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

        foreach($patterns as $pattern) {

            $options = array(
                'grayscale',
                'crop' => true,
                'colorize' => 'FFCC00'
            );
            $url = Image::url($this->imagePath, 300, 300, array_merge($pattern,$options));

            //Check against pattern
            $urlMatch = preg_match('#'.Image::pattern($pattern['url_parameter']).'#',$url,$matches);
            $this->assertEquals($urlMatch,1);

            //Check path
            $parsedPath = Image::parse($url,$pattern);
            $this->assertEquals($parsedPath['path'],$this->imagePath);

            //Check options
            foreach($options as $key => $value) {
                if(is_numeric($key)) {
                    $this->assertTrue($parsedPath['options'][$value]);
                } else {
                    $this->assertEquals($parsedPath['options'][$key], $value);
                }
            }

        }
    }

    public function testServeNoResize()
    {

        $url = Image::url($this->imagePath,null,null,array(
            'grayscale'
        ));
        $response = $this->call('GET', $url);

        $this->assertTrue($response->isOk());

        $sizeManipulated = getimagesizefromstring($response->getContent());
        $this->assertEquals($sizeManipulated[0],$this->imageSize[0]);
        $this->assertEquals($sizeManipulated[1],$this->imageSize[1]);
    }

    public function testServeResizeWidth()
    {
        $url = Image::url($this->imagePath,300);
        $response = $this->call('GET', $url);

        $this->assertTrue($response->isOk());

        $sizeManipulated = getimagesizefromstring($response->getContent());
        $this->assertEquals($sizeManipulated[0],300);
    }

    public function testServeResizeHeight()
    {
        $url = Image::url($this->imagePath,null,300);
        $response = $this->call('GET', $url);

        $this->assertTrue($response->isOk());

        $sizeManipulated = getimagesizefromstring($response->getContent());
        $this->assertEquals($sizeManipulated[1],300);
    }

    public function testServeResizeCrop()
    {
        //Both height and width with crop
        $url = Image::url($this->imagePath,300,300,array(
            'crop' => true
        ));
        $response = $this->call('GET', $url);

        $this->assertTrue($response->isOk());

        $sizeManipulated = getimagesizefromstring($response->getContent());
        $this->assertEquals($sizeManipulated[0],300);
        $this->assertEquals($sizeManipulated[1],300);
    }
    
    public function testServeResizeCropSmall()
    {
        //Both height and width with crop
        $url = Image::url($this->imageSmallPath,300,300,array(
            'crop' => true
        ));
        $response = $this->call('GET', $url);

        $this->assertTrue($response->isOk());

        $sizeManipulated = getimagesizefromstring($response->getContent());
        $this->assertEquals($sizeManipulated[0],300);
        $this->assertEquals($sizeManipulated[1],300);
    }

    public function testServeWrongParameter()
    {
        $url = Image::url($this->imagePath,300,300,array(
            'crop' => true,
            'wrong' => true
        ));
        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\NotFoundHttpException');
        $response = $this->call('GET', $url);
    }

    public function testServeWrongFile()
    {
        $url = Image::url('/wrong123.jpg',300,300,array(
            'crop' => true,
            'wrong' => true
        ));
        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\NotFoundHttpException');
        $response = $this->call('GET', $url);
    }

    public function testServeWrongFormat()
    {
        $url = Image::url('/wrong.jpg',300,300,array(
            'crop' => true
        ));
        $this->setExpectedException('Folklore\Image\Exception\FormatException');
        $response = $this->call('GET', $url);
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
