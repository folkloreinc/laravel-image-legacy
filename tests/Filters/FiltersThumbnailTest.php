<?php

use Folklore\Image\Filters\Thumbnail as ThumbnailFilter;

/**
 * @coversDefaultClass Folklore\Image\Filters\Thumbnail
 */
class FiltersThumbnailTest extends TestCase
{
    protected $filter;

    public function setUp()
    {
        parent::setUp();

        $this->filter = new ThumbnailFilter();
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
            'height' => 150,
            'crop' => false
        ]);
        $size = $thumbnail->getSize();
        $this->assertEquals(100, $size->getWidth());
        $this->assertEquals(100, $size->getHeight());

        $thumbnail = $this->filter->apply($image, [
            'width' => 100,
            'height' => 150,
            'crop' => true
        ]);
        $size = $thumbnail->getSize();
        $this->assertEquals(100, $size->getWidth());
        $this->assertEquals(150, $size->getHeight());
    }
}
