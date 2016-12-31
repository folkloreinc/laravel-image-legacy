<?php

namespace Folklore\Image\Filter;

use Folklore\Image\Contracts\FilterWithValue as FilterWithValueContract;
use Imagine\Image\ImageInterface;

class Gamma implements FilterWithValueContract
{
    public function apply(ImageInterface $image, $value)
    {
        $image->effects()->gamma($value);
        return $image;
    }
}
