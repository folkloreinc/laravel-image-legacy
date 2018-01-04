Image Manipulator
================================================
The Image Manipulator is used to interact with image files on a specific source. When you call `image()->source('local')`, it returns an ImageManipulator.

You can access it from the Image facade:
```php
$manipulator = Image::source(); // default source

$manipulator = image()->source('cloud'); // cloud source
```

Or build it manually
```php
use Folklore\Image\Contracts\ImageManipulator;
$manipulator = app(ImageManipulator::class);

// And setting a source manually
$manipulator->setSource($source);
```

#### Methods

- [`make($path, $filters)`](#make)
- [`open($path)`](#open)
- [`save($image, $path)`](#save)
- [`format($path)`](#format)
- [`setSource($source)`](#setSource)
- [`getSource()`](#getSource)

---

## <a name="make" id="make"></a>`make($path, $filters = [])`
Make an Image object from a path and apply the filters.

##### Arguments
- `(string)` `$path` The path of the image.
- `(array)` `$filters` An array of filters

##### Return
`(Imagine\Image\ImageInterface)` The image object

##### Examples

Create an Image object with the image resized (and cropped) to 300x300 and rotated 180 degrees.
```php
$manipulator = Image::source();
$image = $manipulator->make('path/to/image.jpg', [
    'width' => 300,
    'height' => 300,
    'crop' => true,
    'rotate' => 180
]);
```

---

## <a name="open" id="open"></a>`open($path)`
Open an image from a path, without applying any filters. The image is opened according to the default source specified in the `config/image.php` file.

##### Arguments
- `(string)` `$path` The path of the image.

##### Return
`(Imagine\Image\ImageInterface)` The image object

##### Examples

Open the image path and return an Image object
```php
$manipulator = Image::source();
$image = $manipulator->open('path/to/image.jpg');
```

---

## <a name="save" id="save"></a>`save($image, $path)`
Save an Image object at a given path on the default source.

##### Arguments
- `(Imagine\Image\ImageInterface)` `$image` The image object to be saved
- `(string)` `$path` The path to save the image

##### Return
`(string)` The path of the saved image

##### Examples

Create a resized image and save it to a new path
```php
$manipulator = Image::source();
$image = $manipulator->make('path/to/image.jpg', [
    'width' => 300,
    'height' => 300,
    'crop' => true
]);
$manipulator->save($image, 'path/to/image-resized.jpg');
```

Or save it to a different source:
```php
$manipulator = Image::source();
$image = $manipulator->make('path/to/image.jpg', [
    'width' => 300,
    'height' => 300,
    'crop' => true
]);
Image::source('cloud')->save($image, 'path/to/image-resized.jpg');
```

---

## <a name="format" id="format"></a>`format($path)`
Get the format of an image

##### Arguments
- `(string)` `$path` The path of the image.

##### Return
`string` The format of the image (jpg, png, gif)

##### Examples

Open the image path and return an Image object
```php
$manipulator = Image::source();
$format = $manipulator->format('path/to/image.jpg');
// $format = 'jpg';
```

---

## <a name="setSource" id="setSource"></a>`setSource($source)`
Set the source of the manipulator

##### Arguments
- `(Folklore\Image\Contracts\Source)` `$source` The source that should be used

##### Examples

Open the image path and return an Image object
```php
use Folklore\Image\Contracts\ImageManipulator;
$manipulator = app(ImageManipulator::class);

$source = Image::getSourceManager()->driver('local');
$manipulator->setSource($source);
```

---

## <a name="getSource" id="getSource"></a>`getSource()`
Get the source of the manipulator

##### Return
`(Folklore\Image\Contracts\Source)` `$source` The source that should be used
