<?php

use Folklore\Image\Sources\LocalSource;
use Folklore\Image\ImagineManager;

/**
 * @coversDefaultClass Folklore\Image\Sources\LocalSource
 */
class SourcesLocalSourceTest extends TestCase
{
    protected $source;

    public function setUp()
    {
        parent::setUp();

        $this->source = new LocalSource(app('image.imagine'), app('image.url'), [
            'path' => public_path()
        ]);
    }

    public function tearDown()
    {
        if (file_exists(public_path('image-test.jpg'))) {
            unlink(public_path('image-test.jpg'));
        }

        parent::tearDown();
    }

    /**
     * Test getting the real path
     * @test
     * @covers ::getRealPath
     */
    public function testGetRealPath()
    {
        $path = 'image.jpg';
        $this->assertEquals(public_path($path), $this->source->getRealPath($path));
    }

    /**
     * Test that path exists
     * @test
     * @covers ::pathExists
     */
    public function testPathExists()
    {
        $this->assertTrue($this->source->pathExists('image.jpg'));
        $this->assertFalse($this->source->pathExists('not-found.jpg'));
    }

    /**
     * Test getting format from path
     * @test
     * @covers ::getFormatFromPath
     */
    public function testGetFormatFromPath()
    {
        $this->assertEquals('jpeg', $this->source->getFormatFromPath('image.jpg'));
        $this->assertEquals('png', $this->source->getFormatFromPath('image.png'));
    }

    /**
     * Test opening a path
     * @test
     * @covers ::openFromPath
     */
    public function testOpenFromPath()
    {
        $originalImage = app('image.imagine')->open(public_path('image.jpg'));
        $image = $this->source->openFromPath('image.jpg');
        $this->assertEquals($originalImage->get('png'), $image->get('png'));
    }

    /**
     * Test saving to a path
     * @test
     * @covers ::saveToPath
     */
    public function testSaveToPath()
    {
        $originalImage = app('image.imagine')->open(public_path('image.jpg'));
        $this->source->saveToPath($originalImage, 'image-test.jpg');
        $image = app('image.imagine')->open(public_path('image-test.jpg'));
        $this->assertEquals($originalImage->getSize(), $image->getSize());
    }
}
