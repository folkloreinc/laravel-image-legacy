<?php

namespace Folklore\Image\Tests\Mocks;

class EffectsMock
{
    public $called = null;
    public $callValue = null;

    public function blur($value)
    {
        $this->called = 'blur';
        $this->callValue = $value;
    }

    public function rotate($value)
    {
        $this->called = 'rotate';
        $this->callValue = $value;
    }

    public function colorize($value)
    {
        $this->called = 'colorize';
        $this->callValue = $value;
    }

    public function gamma($value)
    {
        $this->called = 'gamma';
        $this->callValue = $value;
    }

    public function grayscale()
    {
        $this->called = 'grayscale';
    }

    public function interlace()
    {
        $this->called = 'interlace';
    }

    public function negative()
    {
        $this->called = 'negative';
    }

    public function thumbnail($value)
    {
        $this->called = 'thumbnail';
        $this->callValue = $value;
    }
}
