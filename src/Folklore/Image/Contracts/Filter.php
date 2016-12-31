<?php

namespace Folklore\Image\Contracts;

use Imagine\Image\ImageInterface;

interface Filter
{
    public function apply(ImageInterface $image);
}
