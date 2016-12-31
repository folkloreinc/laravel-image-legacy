<?php

namespace Folklore\Image\Contracts;

interface UrlGenerator
{
    public function make($src, $width, $height, $options = []);
    
    public function parse($path, $config = []);
    
    public function pattern($config = []);
}
