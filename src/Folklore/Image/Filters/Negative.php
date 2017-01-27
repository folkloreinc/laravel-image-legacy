<?php

namespace Folklore\Image\Filters;

use Folklore\Image\Contracts\Filter as FilterContract;
use Imagine\Image\ImageInterface;

class Negative implements FilterContract
{
    public function apply(ImageInterface $image)
    {
        $image->effects()->negative();
        return $image;
    }
}
