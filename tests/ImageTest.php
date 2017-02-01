<?php

use Folklore\Image\Contracts\ImageManipulator as ImageManipulatorContract;
use Folklore\Image\Sources\LocalSource;
use Folklore\Image\Sources\FilesystemSource;
use Folklore\Image\SourceManager;
use Folklore\Image\Image;
use Folklore\Image\ImageManipulator;

/**
 * @coversDefaultClass Folklore\Image\Image
 */
class ImageTest extends TestCase
{
    protected $image;

    public function setUp()
    {
        parent::setUp();

        $this->image = app('image');
    }

    /**
     * Test the constructor
     *
     * @test
     * @covers ::__construct
     */
    public function testConstructor()
    {
        $image = new Image(app());
    }

    /**
     * Test source method without a name
     *
     * @test
     * @covers ::source
     */
    public function testSourceWithoutName()
    {
        $factory = $this->image->source();
        $this->assertInstanceOf(ImageManipulatorContract::class, $factory);
        $this->assertInstanceOf(LocalSource::class, $factory->getSource());
    }

    /**
     * Test source method with a name
     *
     * @test
     * @covers ::source
     */
    public function testSourceWithName()
    {
        $factory = $this->image->source('filesystem');
        $this->assertInstanceOf(ImageManipulatorContract::class, $factory);
        $this->assertInstanceOf(FilesystemSource::class, $factory->getSource());
    }

    /**
     * Test source method with an invalid name
     *
     * @test
     * @covers ::source
     * @expectedException Folklore\Image\Exception\InvalidSourceException
     */
    public function testSourceWithInvalidName()
    {
        $factory = $this->image->source('invalid');
    }

    /**
     * Test that source method keeps factory instance
     *
     * @test
     * @covers ::source
     */
    public function testSourceKeepManipulatorInstance()
    {
        $factory = $this->image->source();
        $factorySecond = $this->image->source();
        $this->assertTrue($factory === $factorySecond);
    }

    /**
     * Test that extend call the same method on source manager
     *
     * @test
     * @covers ::extend
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

        $factory = $this->image->extend($driver, $callback);
    }

    /**
     * Test calling source
     *
     * @test
     * @covers ::extend
     * @covers ::__call
     */
    public function testCallingSource()
    {

        $imageManipulator = $this->getMockBuilder(ImageManipulator::class)
            ->disableOriginalConstructor()
            ->setMethods(['testMethod', 'setSource'])
            ->getMock();

        $imageManipulator->expects($this->once())
            ->method('testMethod')
            ->with($this->equalTo('test'));

        $imageManipulator->expects($this->once())
            ->method('setSource')
            ->with($this->image->getSourceManager()->driver());

        app()->bind(ImageManipulatorContract::class, function () use ($imageManipulator) {
            return $imageManipulator;
        });

        $this->image->testMethod('test');
    }

    /**
     * Test the url method
     *
     * @test
     * @covers ::url
     */
    public function testUrl()
    {
        $urlGenerator = app('image.url');

        $path = 'medias/image.jpg';
        $config = [
            'width' => 300,
            'height' => 300,
            'rotate' => 90,
            'format' => '{dirname}/{filters}/{basename}.{extension}',
            'filters_format' => 'image/{filter}',
            'filter_format' => '{key}-{value}',
            'filter_separator' => '/'
        ];
        $this->assertEquals($this->image->url($path, $config), $urlGenerator->make($path, $config));
        $this->assertEquals($this->image->url($path, 300, 300, $config), $urlGenerator->make($path, 300, 300, $config));
    }

    /**
     * Test the pattern method
     *
     * @test
     * @covers ::pattern
     */
    public function testPattern()
    {
        $urlGenerator = app('image.url');

        $config = [
            'format' => '{dirname}/{filters}/{basename}.{extension}',
            'filters_format' => 'image/{filter}',
            'filter_format' => '{key}-{value}',
            'filter_separator' => '/'
        ];
        $this->assertEquals($this->image->pattern($config), $urlGenerator->pattern($config));
    }

    /**
     * Test the pattern method
     *
     * @test
     * @covers ::parse
     */
    public function testParse()
    {
        $urlGenerator = app('image.url');

        $url = '/uploads/image/300x300/rotate-90/negative/image.jpg';
        $config = [
            'format' => '{dirname}/{filters}/{basename}.{extension}',
            'filters_format' => 'image/{filter}',
            'filter_format' => '{key}-{value}',
            'filter_separator' => '/'
        ];
        $this->assertEquals($this->image->parse($url, $config), $urlGenerator->parse($url, $config));
    }

    /**
     * Test the filter method
     *
     * @test
     * @covers ::filter
     * @covers ::hasFilter
     * @covers ::getFilter
     */
    public function testFilter()
    {
        $filter = [
            'width' => 70,
            'height' => 80,
            'crop' => true
        ];
        $this->image->filter('test', $filter);
        $this->assertTrue($this->image->hasFilter('test'));
        $this->assertEquals($filter, $this->image->getFilter('test'));
    }

    /**
     * Test the filter method
     *
     * @test
     * @covers ::getFilters
     * @covers ::setFilters
     */
    public function testGetFilters()
    {
        $filters = [
            'test' => [
                'width' => 70,
                'height' => 80,
                'crop' => true
            ]
        ];
        $this->image->setFilters($filters);
        $this->assertEquals($filters, $this->image->getFilters());
    }

    /**
     * Test the source manager
     *
     * @test
     * @covers ::getSourceManager
     */
    public function testGetSourceManager()
    {
        $this->assertEquals(app('image.source'), $this->image->getSourceManager());
    }

    /**
     * Test the imagine manager
     *
     * @test
     * @covers ::getImagineManager
     */
    public function testGetImagineManager()
    {
        $this->assertEquals(app('image.imagine'), $this->image->getImagineManager());
    }

    /**
     * Test an imagine instance
     *
     * @test
     * @covers ::getImagine
     */
    public function testGetImagine()
    {
        $this->assertEquals(app('image.imagine')->driver(), $this->image->getImagine());
    }

    /**
     * Test the url generator
     *
     * @test
     * @covers ::getUrlGenerator
     */
    public function testGetUrlGenerator()
    {
        $this->assertEquals(app('image.url'), $this->image->getUrlGenerator());
    }
}
