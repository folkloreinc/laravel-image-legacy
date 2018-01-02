Image Manager
================================================
The Image Manager is the main interface with whom you interact. You can access it:

With the facade:
```php
Image::method();
```

Or with the app method:
```php
app('image')->method();
```

For this documentation, we will be using the facade, but any call can be changed to `app('image')`

#### Methods

- [`Image::url($path, $width, $height, $filters)`]()
- [`Image::make($path, $filters)`]()
- [`Image::open($path)`]()
- [`Image::save($image, $path)`]()
- [`Image::source($source)`]()
- [`Image::pattern($config)`]()
- [`Image::parse($url, $config)`]()

---

#### `Image::url($path, $width = null, $height = null, $filters = [])`
Generates an url containing the filters, according to the url format in the config (more info can be found in the [Url Generator](url.md) documentation)

##### Arguments
- `(string)` `$path` The path of the image.
- `(int | array)` `$width` The width of the image. It can be null, or can also be an array of filters.
- `(int)` `$height` The height of the image.
- `(array)` `$filters` An array of filters

##### Return
`(string)` The generated url

##### Examples

```php
$url = Image::url('path/to/image.jpg', 300, 300);
// $url = '/path/to/image-filters(300x300).jpg';
```

You can also omit the size parameters and pass a filters array as the second argument
```php
$url = Image::url('path/to/image.jpg', [
    'width' => 300,
    'height' => 300,
    'rotate' => 180
]);
// $url = '/path/to/image-filters(300x300-rotate(180)).jpg';
```

You can change the format of the url by changing the configuration in the `config/image.php` file or by passing the same options in the filters array. (see [Url Generator](url.md) for available options)

---

#### `Image::make($path, $filters = [])`
Make an Image object from a path and apply the filters.

##### Arguments
- `(string)` `$path` The path of the image.
- `(array)` `$filters` An array of filters

##### Return
`(Imagine\Image\ImageInterface)` The image object

---

#### `Image::open($path)`
Open an image from a path, without applying any filters.

##### Arguments
- `(string)` `$path` The path of the image.

##### Return
`(Imagine\Image\ImageInterface)` The image object

---

#### `Image::save($image, $path)`

##### Arguments
- `(Imagine\Image\ImageInterface)` `$image` The image object to be saved
- `(string)` `$path` The path to save the image

##### Return
`(string)` The path of the saved image

---

#### `Image::source($source)`
Get an Image manipulator for a specific source. (more info can be found in the [Sources](sources.md) documentation)

##### Arguments
- `(string)` `$source` The source name

##### Return
`(Folklore\Image\ImageManipulator)` The image manipulator object, bound the to specified source

---

#### `Image::pattern($config)`

---

#### `Image::parse($url, $config)`
