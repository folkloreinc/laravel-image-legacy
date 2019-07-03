<?php

use Folklore\Image\Filters\Colorize as ColorizeFilter;

/**
 * @coversDefaultClass Folklore\Image\Filters\Colorize
 */
class FiltersColorizeTest extends TestCase
{
    protected $filter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filter = new ColorizeFilter();
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

        $this->filter->apply($imageMock, '#ffffff');

        $this->assertEquals('effects', $imageMock->called);
        $this->assertEquals('colorize', $effectsMock->called);
        $this->assertEquals('#ffffff', (string)$effectsMock->callValue);
    }
}
