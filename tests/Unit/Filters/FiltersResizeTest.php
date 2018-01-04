<?php

use Folklore\Image\Filters\Resize as ResizeFilter;

/**
 * @coversDefaultClass Folklore\Image\Filters\Resize
 */
class FiltersResizeTest extends TestCase
{
    protected $filter;

    public function setUp()
    {
        parent::setUp();

        $this->filter = new ResizeFilter();
    }

    /**
     * Test the apply method
     *
     * @test
     * @covers ::apply
     */
    public function testApply()
    {
        $image = Image::open('image.jpg');

        $thumbnail = $this->filter->apply($image, [
            'width' => 100,
            'height' => 150
        ]);
        $size = $thumbnail->getSize();
        $this->assertEquals(100, $size->getWidth());
        $this->assertEquals(100, $size->getHeight());
    }

    /**
     * Test the apply method with crop
     *
     * @test
     * @covers ::apply
     */
    public function testApplyWithCrop()
    {
        $image = Image::open('image.jpg');

        $thumbnail = $this->filter->apply($image, '100,150,true');
        $size = $thumbnail->getSize();
        $this->assertEquals(100, $size->getWidth());
        $this->assertEquals(150, $size->getHeight());

        $thumbnail = $this->filter->apply($image, '100,150,top_center');
        $size = $thumbnail->getSize();
        $this->assertEquals(100, $size->getWidth());
        $this->assertEquals(150, $size->getHeight());

        $thumbnail = $this->filter->apply($image, '100,150,bottom_right');
        $size = $thumbnail->getSize();
        $this->assertEquals(100, $size->getWidth());
        $this->assertEquals(150, $size->getHeight());
    }
}
