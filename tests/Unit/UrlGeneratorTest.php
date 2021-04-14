<?php

namespace Folklore\Image\Tests\Unit;

use Folklore\Image\Tests\TestCase;
use Folklore\Image\UrlGenerator;
use Folklore\Image\Contracts\FiltersManager;

/**
 * @coversDefaultClass Folklore\Image\UrlGenerator
 */
class UrlGeneratorTest extends TestCase
{
    protected $generator;

    protected $config;

    protected $filters;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new UrlGenerator($this->app['router'], $this->app[FiltersManager::class]);

        $this->config = [
            'pattern' => [
                'format' => '{dirname}/{basename}{filters}.{extension}',
                'filters_format' => '-filters({filter})',
                'filter_format' => '{key}({value})',
                'filter_separator' => '-'
            ]
        ];

        $this->filters = [
            'width' => 300,
            'height' => 300,
            'rotate' => 90,
            'negative' => true
        ];
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
        $value = '{dirname}/{filters}/{basename}.{extension}';
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
        $this->assertEquals('-filters({filter})', $this->generator->getFiltersFormat());
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

    /**
     * Test parsing a path
     * @test
     * @covers ::parse
     * @covers ::patternAndMatches
     * @covers ::parseFilters
     */
    public function testParse()
    {
        $this->generator->setFormat('{dirname}/{filters}/{basename}.{extension}');
        $this->generator->setFiltersFormat('image/{filter}');
        $this->generator->setFilterFormat('{key}-{value}');
        $this->generator->setFilterSeparator('/');

        $path = 'uploads/image/300x300/rotate-90/negative/image.jpg';
        $return = $this->generator->parse($path);
        $this->assertArrayHasKey('path', $return);
        $this->assertArrayHasKey('filters', $return);
        $this->assertEquals('uploads/image.jpg', $return['path']);
        $this->assertEquals($this->filters, $return['filters']);
    }

    /**
     * Test parsing a path with config
     * @test
     * @covers ::parse
     * @covers ::patternAndMatches
     * @covers ::parseFilters
     */
    public function testParseWithConfig()
    {
        $path = 'uploads/image-filters(300x300-rotate(90)-negative).jpg';
        $return = $this->generator->parse($path, $this->config);
        $this->assertArrayHasKey('path', $return);
        $this->assertArrayHasKey('filters', $return);
        $this->assertEquals('uploads/image.jpg', $return['path']);
        $this->assertEquals($this->filters, $return['filters']);
    }

    /**
     * Test getting a pattern
     * @test
     * @covers ::pattern
     * @covers ::patternAndMatches
     */
    public function testPattern()
    {
        $this->generator->setFormat('{dirname}/{filters}/{basename}.{extension}');
        $this->generator->setFiltersFormat('image/{filter}');
        $this->generator->setFilterFormat('{key}-{value}');
        $this->generator->setFilterSeparator('/');

        $pattern = '^(.*?)?\/?(image/(.*?))?/([^\/\.]+?)\.([^\.]+)$';
        $return = $this->generator->pattern();
        $this->assertEquals($pattern, $return);
    }

    /**
     * Test getting a pattern with config
     * @test
     * @covers ::pattern
     * @covers ::patternAndMatches
     */
    public function testPatternWithConfig()
    {
        $pattern = '^(.*?)?\/?([^\/\.]+?)(\-filters\((.*?)\))?\.([^\.]+)$';
        $return = $this->generator->pattern($this->config);
        $this->assertEquals($pattern, $return);
    }

    /**
     * Test making url
     * @test
     * @covers ::make
     * @covers ::getParametersFromFilters
     * @covers ::getFiltersParameter
     */
    public function testMake()
    {
        $this->generator->setFormat('{dirname}/{filters}/{basename}.{extension}');
        $this->generator->setFiltersFormat('image/{filter}');
        $this->generator->setFilterFormat('{key}-{value}');
        $this->generator->setFilterSeparator('/');

        $url = '/uploads/image/300x300/rotate-90/negative/image.jpg';
        $return = $this->generator->make('uploads/image.jpg', $this->filters);
        $this->assertEquals($url, $return);
    }

    /**
     * Test making url with an domain
     * @test
     * @covers ::make
     * @covers ::getParametersFromFilters
     * @covers ::getFiltersParameter
     */
    public function testMakeWithDomain()
    {
        $this->generator->setFormat('{dirname}/{filters}/{basename}.{extension}');
        $this->generator->setFiltersFormat('image/{filter}');
        $this->generator->setFilterFormat('{key}-{value}');
        $this->generator->setFilterSeparator('/');

        $url = 'http://cdn.example.com/uploads/image/300x300/rotate-90/negative/image.jpg';
        $return = $this->generator->make('http://cdn.example.com/uploads/image.jpg', $this->filters);
        $this->assertEquals($url, $return);

        $this->generator->setFormat('https://{host}/{dirname}/{filters}/{basename}.{extension}');
        $url = 'https://cdn.example.com/uploads/image/300x300/image.jpg';
        $return = $this->generator->make('uploads/image.jpg', [
            'width' => 300,
            'height' => 300,
            'host' => 'cdn.example.com'
        ]);
        $this->assertEquals($url, $return);
    }

    /**
     * Test making with size
     * @test
     * @covers ::make
     * @covers ::getParametersFromFilters
     * @covers ::getFiltersParameter
     */
    public function testMakeWithSize()
    {
        $this->generator->setFormat('{dirname}/{filters}/{basename}.{extension}');
        $this->generator->setFiltersFormat('image/{filter}');
        $this->generator->setFilterFormat('{key}-{value}');
        $this->generator->setFilterSeparator('/');

        $url = '/uploads/image/300x300/image.jpg';
        $return = $this->generator->make('uploads/image.jpg', 300, 300);
        $this->assertEquals($url, $return);

        $url = '/uploads/image/300x_/image.jpg';
        $return = $this->generator->make('uploads/image.jpg', 300, null);
        $this->assertEquals($url, $return);

        $url = '/uploads/image/_x300/image.jpg';
        $return = $this->generator->make('uploads/image.jpg', null, 300);
        $this->assertEquals($url, $return);
    }

    /**
     * Test making url with config
     * @test
     * @covers ::make
     * @covers ::getParametersFromFilters
     * @covers ::getFiltersParameter
     */
    public function testMakeWithConfig()
    {
        $url = '/uploads/image-filters(300x300-rotate(90)-negative).jpg';
        $filters = array_merge($this->filters, $this->config);
        $return = $this->generator->make('uploads/image.jpg', $filters);
        $this->assertEquals($url, $return);
    }

    /**
     * Test making url with route
     * @test
     * @covers ::make
     * @covers ::getParametersFromFilters
     * @covers ::getFiltersParameter
     */
    public function testMakeWithRoute()
    {
        app('router')->image('medias/{pattern}', [
            'as' => 'image.test',
            'domain' => 'example.com',
            'pattern' => [
                'format' => '{dirname}/{filters}/{basename}.{extension}',
                'filters_format' => 'image/{filter}',
                'filter_format' => '{key}-{value}',
                'filter_separator' => '/'
            ]
        ]);

        $url = 'http://example.com/medias/uploads/image/300x300/rotate-90/negative/image.jpg';
        $filters = array_merge([
            'route' => 'image.test'
        ], $this->filters);
        $return = $this->generator->make('uploads/image.jpg', $filters);
        $this->assertEquals($url, $return);
    }
}
