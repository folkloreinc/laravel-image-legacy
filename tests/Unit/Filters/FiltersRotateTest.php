<?php

use Folklore\Image\Filters\Rotate as RotateFilter;

/**
 * @coversDefaultClass Folklore\Image\Filters\Rotate
 */
class FiltersRotateTest extends TestCase
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
