<?php

use Folklore\Image\Contracts\ImageFactory as ImageFactoryContract;
use Folklore\Image\Sources\LocalSource;
use Folklore\Image\Sources\FilesystemSource;
use Folklore\Image\SourceManager;

class ImageTest extends ImageTestCase
{
    /**
     * Test source method without a name
     * @test
     */
    public function testSourceWithoutName()
    {
        $factory = Image::source();
        $this->assertInstanceOf(ImageFactoryContract::class, $factory);
        $this->assertInstanceOf(LocalSource::class, $factory->getSource());
    }
    
    /**
     * Test source method with a name
     * @test
     */
    public function testSourceWithName()
    {
        $factory = Image::source('cloud');
        $this->assertInstanceOf(ImageFactoryContract::class, $factory);
        $this->assertInstanceOf(FilesystemSource::class, $factory->getSource());
    }
    
    /**
     * Test source method with an invalid name
     * @test
     * @expectedException Folklore\Image\Exception\InvalidSourceException
     */
    public function testSourceWithInvalidName()
    {
        $factory = Image::source('invalid');
    }
    
    /**
     * Test that source method keeps factory instance
     * @test
     */
    public function testSourceKeepFactoryInstance()
    {
        $factory = Image::source();
        $factorySecond = Image::source();
        $this->assertTrue($factory === $factorySecond);
    }
    
    /**
     * Test that extend call the same method on source manager
     * @test
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
        
        app()->singleton('image.manager.source', function () use ($sourceManager) {
            return $sourceManager;
        });
        
        $factory = Image::extend($driver, $callback);
    }
    
    /**
     * Test routes
     * @test
     */
    public function testRoutes()
    {
        Image::source('cloud')->make('/image.jpg');
        /*Image::routes();
        
        $url = Image::url('/image.jpg', 300, 300, [
            'route' => 'default'
        ]);
        
        $response = $this->call('GET', $url);
        dd($url, $response->getStatusCode());*/
    }
}
