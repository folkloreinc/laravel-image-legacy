<?php namespace Folklore\Image\Events;

class ImageSaved
{
    public $path;

    public function __construct($path)
    {
        $this->path = $path;
    }
}
