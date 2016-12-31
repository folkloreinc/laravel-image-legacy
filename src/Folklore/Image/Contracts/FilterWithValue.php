<?php

namespace Folklore\Image\Contracts;

use Imagine\Image\ImageInterface;

interface FilterWithValue
{
    public function apply(ImageInterface $image, $value);
}
