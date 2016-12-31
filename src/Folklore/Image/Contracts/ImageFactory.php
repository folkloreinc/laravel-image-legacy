<?php

namespace Folklore\Image\Contracts;

use Imagine\Image\ImageInterface;

interface ImageFactory
{
    public function make($path, $config = []);
    
    public function serve($path, $config = []);
    
    public function save(ImageInterface $image, $path);
    
    public function format($path);
    
    public function url($src, $width = null, $height = null, $filters = []);
    
    public function parse($url, $config = []);
    
    public function pattern($config = []);
}
