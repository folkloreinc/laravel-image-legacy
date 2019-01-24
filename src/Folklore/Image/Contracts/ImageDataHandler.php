<?php

namespace Folklore\Image\Contracts;

use Imagine\Image\ImageInterface;

interface ImageDataHandler
{
    public function save(ImageInterface $image, $path, array $opts = []);

    public function get(ImageInterface $image, $format, array $opts = []);
}
