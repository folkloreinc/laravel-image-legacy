<?php

namespace Folklore\Image\Filters;

use Folklore\Image\Contracts\Filter as FilterContract;
use Imagine\Image\ImageInterface;

class Grayscale implements FilterContract
{
    public function apply(ImageInterface $image)
    {
        $image->effects()->grayscale();
        return $image;
    }
}
