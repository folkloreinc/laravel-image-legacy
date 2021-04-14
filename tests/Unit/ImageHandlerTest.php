<?php

namespace Folklore\Image\Tests\Unit;

use Folklore\Image\Tests\TestCase;
use Folklore\Image\ImageHandler;
use Folklore\Image\Filters\Rotate as RotateFilter;
use Folklore\Image\Filters\Resize as ResizeFilter;
use Folklore\Image\Contracts\FiltersManager as FiltersManagerContract;
use Imagine\Image\ImageInterface;

/**
 * @coversDefaultClass Folklore\Image\ImageHandler
 */
class ImageHandlerTest extends TestCase
{
    protected $handler;
    protected $localSource;

    protected function setUp(): void
    {
        parent::setUp();

        $this->localSource = $this->app->make('image.source')->driver('local');
        $this->handler = $this->app->make(ImageHandler::class);
        $this->handler->setSource($this->localSource);
    }

    protected function tearDown(): void
    {
        if (file_exists(public_path('image-test.jpg'))) {
            unlink(public_path('image-test.jpg'));
        }

        parent::tearDown();
    }

    /**
     * Test set and get source
     * @test
     * @covers ::setSource
     * @covers ::getSource
     */
    public function testGetSource()
    {
        $this->assertEquals($this->localSource, $this->handler->getSource());

        $source = $this->app->make('image.source')->driver('filesystem');
        $this->handler->setSource($source);
        $this->assertEquals($source, $this->handler->getSource());
    }

    /**
     * Test open image
     * @test
     * @covers ::open
     */
    public function testOpen()
    {
        $image = $this->handler->open('image.jpg');

        $this->assertInstanceOf(ImageInterface::class, $image);
        $this->assertEquals(public_path('image.jpg'), $image->metadata()->get('filepath'));
        $this->assertEquals(300, $image->getSize()->getWidth());
        $this->assertEquals(300, $image->getSize()->getHeight());
    }

    /**
     * Test getting the format of an image
     * @test
     * @covers ::format
     */
    public function testFormat()
    {
        $format = $this->handler->format('image.jpg');
        $this->assertEquals('jpeg', $format);

        $format = $this->handler->format('image.png');
        $this->assertEquals('png', $format);
    }

    /**
     * Test making an image
     * @test
     * @covers ::make
     * @covers ::applyFilter
     */
    public function testMake()
    {
        $returnImage = $this->handler->open('image.jpg');
        $returnImage = with(new ResizeFilter())->apply($returnImage, [
            'width' => 100,
            'height' => 90,
            'crop' => false
        ]);
        $returnImage = with(new RotateFilter())->apply($returnImage, 90);

        $rotateFilterMock = $this->getMockBuilder(RotateFilter::class)
            ->setMethods(['apply'])
            ->getMock();

        $rotateFilterMock->expects($this->once())
            ->method('apply')
            ->willReturn($returnImage);

        $resizeFilterMock = $this->getMockBuilder(ResizeFilter::class)
            ->setMethods(['apply'])
            ->getMock();

        $resizeFilterMock->expects($this->once())
            ->method('apply')
            ->willReturn($returnImage);

        app('image')->filter('rotate', $rotateFilterMock);
        app('image')->filter('resize', $resizeFilterMock);

        $image = $this->handler->make('image.jpg', [
            'width' => 100,
            'height' => 90,
            'crop' => true,
            'rotate' => 90
        ]);

        $this->assertInstanceOf(ImageInterface::class, $image);
        $this->assertEquals(public_path('image.jpg'), $image->metadata()->get('filepath'));
        $this->assertEquals($returnImage->getSize()->getWidth(), $image->getSize()->getWidth());
        $this->assertEquals($returnImage->getSize()->getHeight(), $image->getSize()->getHeight());
    }

    /**
     * Test making an image with wrong path
     * @test
     * @covers ::make
     */
    public function testMakeWithWrongPath()
    {
        $this->expectException(\Folklore\Image\Exception\FileMissingException::class);
        $image = $this->handler->make('doesnt-exists.jpg');
    }

    /**
     * Test making an image with wrong format
     * @test
     * @covers ::make
     */
    public function testMakeWithWrongFormat()
    {
        $this->expectException(\Folklore\Image\Exception\FormatException::class);
        $image = $this->handler->make('wrong.jpg');
    }

    /**
     * Test making an image with wrong format
     * @test
     * @covers ::make
     */
    public function testMakeWithWrongFilter()
    {
        $this->expectException(\Folklore\Image\Exception\FilterMissingException::class);
        $image = $this->handler->make('image.jpg', [
            'wrong' => true
        ]);
    }

    /**
     * Test saving an image
     * @test
     * @covers ::save
     */
    public function testSave()
    {
        $image = $this->handler->open('image.jpg');
        $this->handler->save($image, 'image-test.jpg');

        $this->assertTrue(file_exists(public_path('image-test.jpg')));
        $imageTest = $this->handler->open('image-test.jpg');
        $this->assertEquals($imageTest->getSize()->getWidth(), $image->getSize()->getWidth());
        $this->assertEquals($imageTest->getSize()->getHeight(), $image->getSize()->getHeight());
    }
}
