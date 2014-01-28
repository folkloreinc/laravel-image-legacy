<?php

class ImageTest extends Orchestra\Testbench\TestCase {

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

    public function setUp()
    {
        parent::setUp();

        
    }

    public function testImageURLisValid()
    {

        $url = Image::url('/images/photo.jpg',300,300,array(
        	'grayscale',
        	'crop' => true,
        	'colorize' => 'FFCC00'
        ));

        $urlMatch = preg_match('#'.Image::getPattern().'#',$url,$matches);

        $this->assertEquals($urlMatch,1);
    }

}