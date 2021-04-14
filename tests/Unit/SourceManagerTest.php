<?php

namespace Folklore\Image\Tests\Unit;

use Folklore\Image\Tests\TestCase;
use Folklore\Image\SourceManager;
use Folklore\Image\Sources\LocalSource;
use Folklore\Image\Sources\FilesystemSource;

/**
 * @coversDefaultClass Folklore\Image\SourceManager
 */
class SourceManagerTest extends TestCase
{
    protected $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = new SourceManager($this->app);
    }

    /**
     * Test getting the local driver
     * @test
     * @covers ::createDriver
     * @covers ::createLocalDriver
     */
    public function testLocalDriver()
    {
        $driver = $this->manager->driver('local');
        $config = app('config')->get('image.sources.local');
        $this->assertEquals(new LocalSource(app('image.imagine')->driver(), app('image.url'), $config), $driver);
    }

    /**
     * Test getting the filesystem driver
     * @test
     * @covers ::createDriver
     * @covers ::createFilesystemDriver
     */
    public function testFilesystemDriver()
    {
        $driver = $this->manager->driver('filesystem');
        $config = app('config')->get('image.sources.filesystem');
        $this->assertEquals(new FilesystemSource(app('image.imagine')->driver(), app('image.url'), $config), $driver);
    }

    /**
     * Test getting a custom driver
     * @test
     * @covers ::createDriver
     */
    public function testCustomDriver()
    {
        $this->app['config']->set('image.sources.custom', [
            'driver' => 'custom'
        ]);
        $this->manager->extend('custom', function () {
            return 'custom';
        });
        $driver = $this->manager->driver('custom');
        $this->assertEquals('custom', $driver);
    }

    /**
     * Test get and set default driver
     * @test
     * @covers ::getDefaultDriver
     * @covers ::setDefaultDriver
     */
    public function testGetDefaultDriver()
    {
        $defaultDriver = $this->app['config']->get('image.source');
        $this->assertEquals($defaultDriver, $this->manager->getDefaultDriver());

        $driver = 'filesystem';
        $this->manager->setDefaultDriver($driver);
        $this->assertEquals($driver, $this->manager->getDefaultDriver());
    }
}
