<?php

namespace Folklore\Image\Tests\Unit\Sources;

use Folklore\Image\Tests\TestCase;
use Folklore\Image\Sources\LocalSource;
use Folklore\Image\ImagineManager;

/**
 * @coversDefaultClass Folklore\Image\Sources\LocalSource
 */
class LocalSourceTest extends TestCase
{
    protected $source;

    protected function setUp(): void
    {
        parent::setUp();

        $config = $this->app['config']->get('image.sources.local');
        $this->source = new LocalSource(app('image.imagine')->driver(), app('image.url'), $config);
    }

    protected function tearDown(): void
    {
        if (file_exists(public_path('image-test.jpg'))) {
            unlink(public_path('image-test.jpg'));
        }

        if (file_exists(public_path('image-filters(300x300).jpg'))) {
            unlink(public_path('image-filters(300x300).jpg'));
        }

        parent::tearDown();
    }

    /**
     * Test getting the full path
     * @test
     * @covers ::__construct
     * @covers ::getFullPath
     */
    public function testGetFullPath()
    {
        $path = 'image.jpg';
        $this->assertEquals(public_path($path), $this->source->getFullPath($path));
        $this->assertNull($this->source->getFullPath('test/image.jpg'));
        $this->assertNull($this->source->getFullPath('not-found.jpg'));
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
     * Test gettingFilesFromPath
     * @test
     * @covers ::getFilesFromPath
     */
    public function testGetFilesFromPath()
    {
        if (!file_exists(public_path('image-filters(300x300).jpg'))) {
            copy(public_path('image.jpg'), public_path('image-filters(300x300).jpg'));
        }

        $originalFiles = [
            public_path('image-filters(300x300).jpg'),
            public_path('image.jpg'),
            public_path('image.png'),
            public_path('image_small.jpg'),
            public_path('wrong.jpg')
        ];
        $files = $this->source->getFilesFromPath('/');
        sort($originalFiles);
        sort($files);
        $this->assertEquals($originalFiles, $files);

        $originalFiles = [
            public_path('image-filters(300x300).jpg'),
            public_path('image.jpg')
        ];
        $files = $this->source->getFilesFromPath('image.jpg');
        sort($originalFiles);
        sort($files);
        $this->assertEquals($originalFiles, $files);

        $files = $this->source->getFilesFromPath('not-found');
        $this->assertEquals([], $files);
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
