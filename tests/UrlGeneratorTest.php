<?php

use Folklore\Image\UrlGenerator;

/**
 * @coversDefaultClass Folklore\Image\UrlGenerator
 */
class UrlGeneratorTest extends ImageTestCase
{
    protected $generator;

    public function setUp()
    {
        parent::setUp();

        $this->generator = new UrlGenerator(app('image'), app('image.router'));
    }

    /**
     * Test get and set format
     * @test
     * @covers ::getFormat
     * @covers ::setFormat
     */
    public function testGetFormat()
    {
        $this->assertEquals('{dirname}/{basename}{filters}.{extension}', $this->generator->getFormat());
        $value = '{dirname}/{basename}/{filters}.{extension}';
        $this->generator->setFormat($value);
        $this->assertEquals($value, $this->generator->getFormat());
    }

    /**
     * Test get and set filters format
     * @test
     * @covers ::getFiltersFormat
     * @covers ::setFiltersFormat
     */
    public function testGetFiltersFormat()
    {
        $this->assertEquals('-image({filter})', $this->generator->getFiltersFormat());
        $value = 'image/{filter}';
        $this->generator->setFiltersFormat($value);
        $this->assertEquals($value, $this->generator->getFiltersFormat());
    }

    /**
     * Test get and set filter format
     * @test
     * @covers ::getFilterFormat
     * @covers ::setFilterFormat
     */
    public function testGetFilterFormat()
    {
        $this->assertEquals('{key}({value})', $this->generator->getFilterFormat());
        $value = '{key}-{value}';
        $this->generator->setFilterFormat($value);
        $this->assertEquals($value, $this->generator->getFilterFormat());
    }

    /**
     * Test get and set filter separator
     * @test
     * @covers ::getFilterSeparator
     * @covers ::setFilterSeparator
     */
    public function testGetFilterSeparator()
    {
        $this->assertEquals('-', $this->generator->getFilterSeparator());
        $value = '/';
        $this->generator->setFilterSeparator($value);
        $this->assertEquals($value, $this->generator->getFilterSeparator());
    }
}
