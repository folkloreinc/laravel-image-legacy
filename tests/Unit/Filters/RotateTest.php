<?php

namespace Folklore\Image\Tests\Unit\Filters;

use Folklore\Image\Tests\TestCase;
use Folklore\Image\Filters\Rotate as RotateFilter;
use Folklore\Image\Tests\Mocks\EffectsMock;
use Folklore\Image\Tests\Mocks\ImageMock;

/**
 * @coversDefaultClass Folklore\Image\Filters\Rotate
 */
class RotateTest extends TestCase
{
    protected $filter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filter = new RotateFilter();
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

        $this->filter->apply($imageMock, 30);
        $this->assertEquals('rotate', $imageMock->called);
        $this->assertEquals(30, $imageMock->callValue);
    }
}
