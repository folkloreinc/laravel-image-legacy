<?php

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Folklore\Image\Exception\FormatException;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app->instance('path.public', __DIR__.'/fixture');

        $app['config']->set('image.source', 'local');
        $app['config']->set('image.sources', [
            'local' => [
                'driver' => 'local',
                'path' => public_path()
            ],
            'filesystem' => [
                'driver' => 'filesystem',
                'disk' => 'local',
                'path' => null,
                'cache' => true,
                'cache_path' => storage_path('image/cache')
            ]
        ]);

        $app['config']->set('filesystems.disks.local.root', public_path());
    }

    protected function getPackageProviders($app)
    {
        return [
            \Folklore\Image\ImageServiceProvider::class
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Image' => \Folklore\Image\Support\Facades\Image::class
        ];
    }
}
