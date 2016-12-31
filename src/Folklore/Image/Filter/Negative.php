<?php

namespace Folklore\Image\Filter;

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
