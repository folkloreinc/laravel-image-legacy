<?php

namespace Folklore\Image\Filters;

use Folklore\Image\Contracts\Filter as FilterContract;
use Imagine\Image\ImageInterface;

class Interlace implements FilterContract
{
    public function apply(ImageInterface $image)
    {
        $image->interlace(ImageInterface::INTERLACE_LINE);
        return $image;
    }
}
