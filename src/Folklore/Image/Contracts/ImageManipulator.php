<?php

namespace Folklore\Image\Contracts;

use Imagine\Image\ImageInterface;

interface ImageManipulator
{
    public function make($path, $config = []);

    public function save(ImageInterface $image, $path);

    public function format($path);
}
