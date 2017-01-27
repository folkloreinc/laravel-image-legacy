<?php

namespace Folklore\Image\Filters;

use Folklore\Image\Contracts\FilterWithValue as FilterWithValueContract;
use Imagine\Image\ImageInterface;

class Blur implements FilterWithValueContract
{
    public function apply(ImageInterface $image, $value)
    {
        $image->effects()->blur($value);
        return $image;
    }
}
