<?php

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Folklore\Image\Exception\FormatException;

//Add getimagesizefromstring for PHP 5.3
if (!function_exists('getimagesizefromstring')) {
    function getimagesizefromstring($data)
    {
        $uri = 'data://application/octet-stream;base64,' . base64_encode($data);
        return getimagesize($uri);
    }
}

class ImageTest extends Orchestra\Testbench\TestCase {

    protected $imagePath = '/image.jpg';
    protected $imageSize;

    public function setUp()
    {
        parent::setUp();

        Image::deleteManipulated($this->imagePath);

        $this->imageSize = getimagesize(public_path().$this->imagePath);
    }



    public function testURLisValid()
    {

        $options = array(
            'grayscale',
            'crop' => true,
            'colorize' => 'FFCC00'
        );
        $url = Image::url($this->imagePath, 300, 300, $options);

        //Check against pattern
        $urlMatch = preg_match('#'.Image::getPattern().'#',$url,$matches);
        $this->assertEquals($urlMatch,1);

        //Check path
        $parsedPath = Image::parse($url);
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



    protected function getPackageProviders()
    {
        return array('Folklore\Image\ImageServiceProvider');
    }

    protected function getPackageAliases()
    {
        return array(
            'Image' => 'Folklore\Image\Facades\Image'
        );
    }

    protected function getApplicationPaths()
    {
        $basePath = realpath(__DIR__.'/../vendor/orchestra/testbench/src/fixture');

        return array(
            'app'     => "{$basePath}/app",
            'public'  => realpath(__DIR__.'/fixture'),
            'base'    => $basePath,
            'storage' => "{$basePath}/app/storage",
        );
    }

}