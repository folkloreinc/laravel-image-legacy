<?php

use Folklore\Image\Contracts\ImageManipulator as ImageManipulatorContract;
use Folklore\Image\Sources\LocalSource;
use Folklore\Image\Sources\FilesystemSource;
use Folklore\Image\SourceManager;

class ImageTest extends TestCase
{
    /**
     * Test source method without a name
     * @test
     */
    public function testSourceWithoutName()
    {
        $factory = Image::source();
        $this->assertInstanceOf(ImageManipulatorContract::class, $factory);
        $this->assertInstanceOf(LocalSource::class, $factory->getSource());
    }

    /**
     * Test source method with a name
     * @test
     */
    public function testSourceWithName()
    {
        $factory = Image::source('filesystem');
        $this->assertInstanceOf(ImageManipulatorContract::class, $factory);
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
    public function testSourceKeepManipulatorInstance()
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

        app()->singleton('image.source', function () use ($sourceManager) {
            return $sourceManager;
        });

        $factory = Image::extend($driver, $callback);
    }
}
