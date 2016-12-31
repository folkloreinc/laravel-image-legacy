<?php

namespace Folklore\Image\Contracts;

interface ImageFactory
{
    public function make($path, $options = []);
    
    public function url($src, $width = null, $height = null, $options = []);
    
    public function parse($url);
}
