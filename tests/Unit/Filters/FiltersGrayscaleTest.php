<?php

use Folklore\Image\Filters\Grayscale as GrayscaleFilter;

/**
 * @coversDefaultClass Folklore\Image\Filters\Grayscale
 */
class FiltersGrayscaleTest extends TestCase
{
    protected $filter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filter = new GrayscaleFilter();
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

        $this->assertEquals('effects', $imageMock->called);
        $this->assertEquals('grayscale', $effectsMock->called);
    }
}
