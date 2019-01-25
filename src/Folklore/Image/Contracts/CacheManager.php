<?php

namespace Folklore\Image\Contracts;

use Imagine\Image\ImageInterface;

interface CacheManager
{
    public function put(ImageInterface $image, string $url, string $directory = null);
}
