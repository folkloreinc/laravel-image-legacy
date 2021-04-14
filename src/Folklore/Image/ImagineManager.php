<?php namespace Folklore\Image;

use Illuminate\Support\Manager;

class ImagineManager extends Manager
{
    /**
     * Create an instance of the Imagine Gd driver.
     *
     * @return \Imagine\Gd\Imagine
     */
    protected function createGdDriver()
    {
        return new \Imagine\Gd\Imagine();
    }

    /**
     * Create an instance of the Imagine Imagick driver.
     *
     * @return \Imagine\Imagick\Imagine
     */
    protected function createImagickDriver()
    {
        return new \Imagine\Imagick\Imagine();
    }

    /**
     * Create an instance of the Imagine Gmagick driver.
     *
     * @return \Imagine\Gmagick\Imagine
     */
    protected function createGmagickDriver()
    {
        return new \Imagine\Gmagick\Imagine();
    }

    /**
     * Get the default image driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->config['image.driver'];
    }

    /**
     * Set the default image driver name.
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultDriver($name)
    {
        $this->config['image.driver'] = $name;
    }
}
