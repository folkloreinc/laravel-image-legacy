<?php

namespace Folklore\Image\Tests\Unit\Filters;

use Folklore\Image\Tests\TestCase;
use Folklore\Image\Filters\Negative as NegativeFilter;
use Folklore\Image\Tests\Mocks\EffectsMock;
use Folklore\Image\Tests\Mocks\ImageMock;

/**
 * @coversDefaultClass Folklore\Image\Filters\Negative
 */
class NegativeTest extends TestCase
{
    protected $filter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filter = new NegativeFilter();
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
        $this->assertEquals('negative', $effectsMock->called);
    }
}
