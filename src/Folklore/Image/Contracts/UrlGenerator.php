<?php

namespace Folklore\Image\Contracts;

interface UrlGenerator
{
    public function make($src, $width = null, $height = null, $filters = []);
    
    public function pattern($config = []);
    
    public function parse($path, $config = []);
}
