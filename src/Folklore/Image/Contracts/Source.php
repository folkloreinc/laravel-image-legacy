<?php

namespace Folklore\Image\Contracts;

use Imagine\Image\ImageInterface;

interface Source
{
    public function pathExists($path);
    
    public function getFormatFromPath($path);
    
    public function openFromPath($path);
    
    public function saveToPath(ImageInterface $image, $path);
    
    public function getFilesFromPath($path);
}
