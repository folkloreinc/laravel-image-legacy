<?php

use Folklore\Image\Filters\Gamma as GammaFilter;

/**
 * @coversDefaultClass Folklore\Image\Filters\Gamma
 */
class FiltersGammaTest extends TestCase
{
    protected $filter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filter = new GammaFilter();
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

        $this->filter->apply($imageMock, 0.1);

        $this->assertEquals('effects', $imageMock->called);
        $this->assertEquals('gamma', $effectsMock->called);
        $this->assertEquals(0.1, $effectsMock->callValue);
    }
}
