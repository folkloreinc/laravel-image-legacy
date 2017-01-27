<?php

namespace Folklore\Image\Filters;

use Folklore\Image\Contracts\FilterWithValue as FilterWithValueContract;
use Imagine\Image\ImageInterface;

class Rotate implements FilterWithValueContract
{
    public function apply(ImageInterface $image, $value)
    {
        return $image->rotate($value);
    }
}
