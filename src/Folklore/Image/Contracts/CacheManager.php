<?php

namespace Folklore\Image\Contracts;

use Imagine\Image\ImageInterface;

interface CacheManager
{
    public function put(ImageInterface $image, $url, $directory = null);
}
