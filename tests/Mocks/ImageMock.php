<?php

namespace Folklore\Image\Tests\Mocks;

use Imagine\Image\Image as ImageGd;
use Imagine\Gd\Effects as EffectsGd;
use Imagine\Image\ImageInterface;
use Imagine\Image\Fill\FillInterface;
use Imagine\Image\Palette\PaletteInterface;
use Imagine\Image\Palette\Color\ColorInterface;
use Imagine\Image\PointInterface;
use Imagine\Image\BoxInterface;
use Imagine\Image\ProfileInterface;
use Imagine\Image\Palette\RGB as RGBPalette;
use Imagine\Image\Point;
use Imagine\Image\Box;

class ImageMock implements ImageInterface
{
    public $called = false;

    public $callValue = null;

    protected $effects;

    public function __construct($effects)
    {
        $this->effects = $effects;
    }

    public function effects()
    {
        $this->called = 'effects';
        return $this->effects;
    }

    public function get($format, array $options = array())
    {
    }

    public function __toString()
    {
    }

    public function draw()
    {
    }

    public function getSize()
    {
        return new Box(300, 300);
    }

    public function mask()
    {
    }

    public function histogram()
    {
    }

    public function getColorAt(PointInterface $point)
    {
    }

    public function layers()
    {
    }

    public function interlace($scheme)
    {
        $this->called = 'interlace';
    }

    public function palette()
    {
        return new RGBPalette();
    }

    public function usePalette(PaletteInterface $palette)
    {
    }

    public function profile(ProfileInterface $profile)
    {
    }

    public function metadata()
    {
    }

    public function copy()
    {
        return $this;
    }

    public function crop(PointInterface $start, BoxInterface $size)
    {
    }

    public function resize(BoxInterface $size, $filter = ImageInterface::FILTER_UNDEFINED)
    {
    }

    public function rotate($angle, ColorInterface $background = null)
    {
        $this->called = 'rotate';
        $this->callValue = $angle;
    }

    public function paste(ImageInterface $image, PointInterface $start, $alpha = 100)
    {
    }

    public function save($path = null, array $options = array())
    {
    }

    public function show($format, array $options = array())
    {
    }

    public function flipHorizontally()
    {
    }

    public function flipVertically()
    {
    }

    public function strip()
    {
    }

    public function thumbnail(
        BoxInterface $size,
        $mode = self::THUMBNAIL_INSET,
        $filter = ImageInterface::FILTER_UNDEFINED
    ) {
    }

    public function applyMask(ImageInterface $mask)
    {
    }

    public function fill(FillInterface $fill)
    {
    }
}
