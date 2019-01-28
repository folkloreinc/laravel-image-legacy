<?php

namespace Folklore\Image\Contracts;

use Imagine\Image\ImageInterface;

interface ImageHandler
{
    public function make($path, $config = []);

    public function open($path);

    public function save(ImageInterface $image, $path);

    public function format($path);

    public function getSource();

    public function setSource(Source $source);
}
