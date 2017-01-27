<?php

namespace Folklore\Image\Filters;

use Folklore\Image\Contracts\FilterWithValue as FilterWithValueContract;
use Imagine\Image\ImageInterface;

class Colorize implements FilterWithValueContract
{
    public function apply(ImageInterface $image, $value)
    {
        $palettes = ['RGB','CMYK'];
        $parts = explode(',', $value);
        $color = $parts[0];
        if (isset($parts[1]) && in_array(strtoupper($parts[1]), $palettes)) {
            $className = '\\Imagine\\Image\\Palette\\'.strtoupper($parts[1]);
            $palette = new $className();
        } else {
            $palette = $image->palette();
        }
        $color = $palette->color($color);
        $image->effects()->colorize($color);
        return $image;
    }
}
