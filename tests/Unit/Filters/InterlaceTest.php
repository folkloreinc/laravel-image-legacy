<?php

namespace Folklore\Image\Tests\Unit\Filters;

use Folklore\Image\Tests\TestCase;
use Folklore\Image\Filters\Interlace as InterlaceFilter;
use Folklore\Image\Tests\Mocks\EffectsMock;
use Folklore\Image\Tests\Mocks\ImageMock;

/**
 * @coversDefaultClass Folklore\Image\Filters\Interlace
 */
class InterlaceTest extends TestCase
{
    protected $filter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filter = new InterlaceFilter();
    }

    /**
     * Test the apply method
     *
     * @test
     * @covers ::apply
     */
    public function testApply()
    {
        $effectsMock = new EffectsMock();
        $imageMock = new ImageMock($effectsMock);

        $this->filter->apply($imageMock);
        $this->assertEquals('interlace', $imageMock->called);
    }
}
