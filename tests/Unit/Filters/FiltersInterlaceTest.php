<?php

use Folklore\Image\Filters\Interlace as InterlaceFilter;

/**
 * @coversDefaultClass Folklore\Image\Filters\Interlace
 */
class FiltersInterlaceTest extends TestCase
{
    protected $filter;

    public function setUp()
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
