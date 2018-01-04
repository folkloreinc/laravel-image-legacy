Sources
================================================
In Laravel Image, the place where your images files are stored is called a source. You can have multiple source and each source can implements a different driver. Currently there is two drivers supported: `local` and `filesystem`. The last one is based on [Laravel Filesystems](https://laravel.com/docs/5.5/filesystem) and it supports all the same drivers (Amazon S3, Ftp, ...). Just specify a disk that is defined in `config/filesystems.php` and you are good to go.

Here is the default sources configuration from `config/image.php`:

```php

/*
|--------------------------------------------------------------------------
| Default Source
|--------------------------------------------------------------------------
|
| This option define the default source to be used by the Image facade. The
| source determine where the image files are read and saved.
|
*/
'source' => 'public',

/*
|--------------------------------------------------------------------------
| Sources
|--------------------------------------------------------------------------
|
| The list of sources where you store images.
|
| Supported driver: "local", "filesystem"
|
*/
'sources' => [

    'public' => [
        // The local driver use a local path on the machine.
        'driver' => 'local',

        // The path where the images are stored.
        'path' => public_path()
    ],

    'cloud' => [
        // The filesystem driver lets you use the filesystem from laravel.
        'driver' => 'filesystem',

        // The filesystem disk where the images are stored.
        'disk' => 'public',

        // The path on the disk where the images are stored. If set to null,
        // it will start from the root.
        'path' => null,

        // Cache the file on local machine. It can be useful for remote files.
        'cache' => true,

        // The path where you want to put cached files
        'cache_path' => storage_path('image/cache')
    ]

],

```

When you interact with the `Image` facade or the `image()` helper, by default the images are taken from the default source defined in the config. If you want to use another source, you can do:
```php
$image = image()->source('cloud')->open('path/to/an/image.jpg');

// or
$image = image()->source('cloud')->make('path/to/an/image.jpg', [
    'width' => 100,
    'height' => 100
]);
```

Be carefull the `$image` object returned by the `make()` and `open()` methods implements a `save()` method. This method will save on your local disk only. To save an image on a specific source, use:
```php
image()->cloud()->save($image, 'path/on/the/source/image.jpg');
```
