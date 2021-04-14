<?php namespace Folklore\Image;

use Folklore\Image\Contracts\UrlGenerator as UrlGeneratorContract;
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
        $imagine = $this->container['image.imagine']->driver();
        $urlGenerator = $this->container->make(UrlGeneratorContract::class);
        return new LocalSource($imagine, $urlGenerator, $config);
    }

    /**
     * Create an instance of the Imagine Imagick driver.
     *
     * @return \Imagine\Imagick\Imagine
     */
    protected function createFilesystemDriver($config)
    {
        $imagine = $this->container['image.imagine']->driver();
        $urlGenerator = $this->container->make(UrlGeneratorContract::class);
        return new FilesystemSource($imagine, $urlGenerator, $config);
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
        $config = $this->config['image.sources.'.$source];
        if (!$config) {
            throw new InvalidSourceException("Source [$source] not found.");
        }
        $driver = $config['driver'];
        $method = 'create'.ucfirst($driver).'Driver';

        // We'll check to see if a creator method exists for the given driver. If not we
        // will check for a custom driver creator, which allows developers to create
        // drivers using their own customized driver creator Closure to create it.
        if (isset($this->customCreators[$driver])) {
            return $this->customCreators[$driver]($this->container, $config);
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
        return $this->config['image.source'];
    }

    /**
     * Set the default image driver name.
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultDriver($name)
    {
        $this->config['image.source'] = $name;
    }
}
