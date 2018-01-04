<?php

use Folklore\Image\Filters\Blur as BlurFilter;

/**
 * @coversDefaultClass Folklore\Image\Filters\Blur
 */
class FiltersBlurTest extends TestCase
{
    protected $filter;

    public function setUp()
    {
        parent::setUp();

        $this->filter = new BlurFilter();
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

        $this->assertEquals('effects', $imageMock->called);
        $this->assertEquals('blur', $effectsMock->called);
        $this->assertEquals(30, $effectsMock->callValue);
    }
}
