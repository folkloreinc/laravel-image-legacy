<?php namespace Folklore\Image;

use Folklore\Image\Sources\LocalSource;
use Folklore\Image\Sources\FilesystemSource;
use Folklore\Image\Exception\InvalidSourceException;

use Illuminate\Support\Manager;

use Imagine\Image\AbstractImagine as Imagine;

class SourceManager extends Manager
{
    /**
     * Create an instance of the Imagine Gd driver.
     *
     * @return \Imagine\Gd\Imagine
     */
    protected function createLocalDriver($config)
    {
        return new LocalSource($this->app['image.imagine'], $this->app['image.url'], $config);
    }

    /**
     * Create an instance of the Imagine Imagick driver.
     *
     * @return \Imagine\Imagick\Imagine
     */
    protected function createFilesystemDriver($config)
    {
        return new FilesystemSource($this->app['image.imagine'], $config);
    }

    /**
     * Create a new driver instance.
     *
     * @param  string  $driver
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    protected function createDriver($source)
    {
        $config = $this->app['config']['image.sources.'.$source];
        if (!$config) {
            throw new InvalidSourceException("Source [$source] not found.");
        }
        $driver = $config['driver'];
        $method = 'create'.ucfirst($driver).'Driver';

        // We'll check to see if a creator method exists for the given driver. If not we
        // will check for a custom driver creator, which allows developers to create
        // drivers using their own customized driver creator Closure to create it.
        if (isset($this->customCreators[$driver])) {
            return $this->customCreators[$driver]($this->app, $config);
        } elseif (method_exists($this, $method)) {
            return $this->$method($config);
        }

        throw new InvalidSourceException("Driver [$driver] not supported for source [$source].");
    }

    /**
     * Get the default image driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['image.source'];
    }

    /**
     * Set the default image driver name.
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultDriver($name)
    {
        $this->app['config']['image.source'] = $name;
    }
}
