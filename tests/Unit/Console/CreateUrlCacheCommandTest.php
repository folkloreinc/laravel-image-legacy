<?php

namespace Folklore\Image\Tests\Unit\Console;

use Folklore\Image\Tests\TestCase;
use Folklore\Image\Filters\CreateUrlCacheCommand;

/**
 * @coversDefaultClass Folklore\Image\Console\CreateUrlCacheCommand
 */
class CreateUrlCacheCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        $url = $this->app['image.url']->make('/image.jpg', ['negative']);
        $cachePath = public_path($url);
        if (file_exists($cachePath)) {
            unlink($cachePath);
        }

        parent::tearDown();
    }

    /**
     * Test the apply method
     *
     * @test
     * @covers ::handle
     */
    public function testRun()
    {
        if (method_exists($this, 'withoutMockingConsoleOutput')) {
            $this->withoutMockingConsoleOutput();
        }

        $returnCode = $this->artisan('image:create_url_cache', [
            'url' => '/image.jpg',
            '--filters' => ['negative']
        ]);
        $this->assertEquals(0, $returnCode);

        $url = $this->app['image.url']->make('/image.jpg', ['negative']);
        $cachePath = public_path($url);
        $this->assertTrue(file_exists($cachePath));
    }
}
