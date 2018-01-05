ImageHandler
=====================

The Image Handler is used to interact with image files on a specific source. When you call `image()->source('local')`, it returns an ImageHandler.

You can access it from the Image facade:
```php
$handler = Image::source(); // default source

$handler = image()->source('cloud'); // cloud source
```

Or build it manually
```php
use Folklore\Image\Contracts\ImageHandler;
$handler = app(ImageHandler::class);

// And setting a source manually
$handler->setSource($source);
```

#### Methods

- [`make($path, $options)`](#make)
- [`open($path)`](#open)
- [`save($image, $path)`](#save)
- [`format($path)`](#format)
- [`thumbnail($image, $width, $height, $crop)`](#thumbnail)
- [`getSource()`](#getSource)
- [`setSource($source)`](#setSource)
- [`getMemoryLimit()`](#getMemoryLimit)
- [`setMemoryLimit($limit)`](#setMemoryLimit)
- [`getImagineManager()`](#getImagineManager)
- [`getImagine()`](#getImagine)


---

<a name="make" id="make"></a>
### `make($path, $options = array())`

Make an image and apply options

#### Arguments
- `$path` `(string)` The path of the image 
- `$options` `(array)` The manipulations to apply on the image 

#### Return
`(\Imagine\Image\ImageInterface)`

#### Examples

Create an Image object with the image resized (and cropped) to 300x300
and rotated 180 degrees.
```php
$handler = Image::source();
$image = $handler->make('path/to/image.jpg', [
    'width' => 300,
    'height' => 300,
    'crop' => true,
    'rotate' => 180
]);
```


---

<a name="open" id="open"></a>
### `open($path)`

Open an image from the source

#### Arguments
- `$path` `(string)` The path of the image 

#### Return
`(\Imagine\Image\ImageInterface)`

#### Examples

Open the image path and return an Image object
```php
$handler = Image::source();
$image = $handler->open('path/to/image.jpg');
```


---

<a name="save" id="save"></a>
### `save($image, $path)`

Save an image to the source

#### Arguments
- `$image` `(\Imagine\Image\ImageInterface)` The image to save 
- `$path` `(string)` The path where you want to save the image 

#### Return
`(string)`

#### Examples

Create a resized image and save it to a new path
```php
$handler = Image::source();
$image = $handler->make('path/to/image.jpg', [
    'width' => 300,
    'height' => 300,
    'crop' => true
]);
$handler->save($image, 'path/to/image-resized.jpg');
```

Or save it to a different source:
```php
$handler = Image::source();
$image = $handler->make('path/to/image.jpg', [
    'width' => 300,
    'height' => 300,
    'crop' => true
]);
Image::source('cloud')->save($image, 'path/to/image-resized.jpg');
```


---

<a name="format" id="format"></a>
### `format($path)`

Return an URL to process the image

#### Arguments
- `$path` `(string)` The path of the image 

#### Return
`(string)` The format fo the image
        

#### Examples

Open the image path and return an Image object
```php
$handler = Image::source();
$format = $handler->format('path/to/image.jpg');
// $format = 'jpg';


---

<a name="thumbnail" id="thumbnail"></a>
### `thumbnail($image, $width = null, $height = null, $crop = true)`

Create a thumbnail from an image

#### Arguments
- `$image` `(\Imagine\Image\ImageInterface|string)` An image instance or the path to an image 
- `$width` `(integer)` The maximum width of the thumbnail 
- `$height` `(integer)` The maximum height of the thumbnail 
- `$crop` `(boolean|string)` If this is set to true, it match the exact size provided. You can also set a position for the cropped image (ex: &#039;top left&#039;) 

#### Return
`(\Imagine\Image\ImageInterface)`


---

<a name="getSource" id="getSource"></a>
### `getSource()`

Get the image source

#### Return
`(\Folklore\Image\Contracts\Source)` The source that is used
        


---

<a name="setSource" id="setSource"></a>
### `setSource($source)`

Set the image source

Open the image path and return an Image object
```php
use Folklore\Image\Contracts\ImageHandler;
$handler = app(ImageHandler::class);

$source = Image::getSourceManager()-&gt;driver(&#039;local&#039;);
$handler-&gt;setSource($source);
```

#### Arguments
- `$source` `(\Folklore\Image\Contracts\Source)` The source of the factory 

#### Return
`(\Folklore\Image\ImageHandler)`


---

<a name="getMemoryLimit" id="getMemoryLimit"></a>
### `getMemoryLimit()`

Get the memory limit

#### Return
`(string)`


---

<a name="setMemoryLimit" id="setMemoryLimit"></a>
### `setMemoryLimit($limit)`

Set the memory limit

#### Arguments
- `$limit` `(string)` The memory limit 

#### Return
`(\Folklore\Image\ImageHandler)`


---

<a name="getImagineManager" id="getImagineManager"></a>
### `getImagineManager()`

Get the imagine manager

#### Return
`(\Folklore\Image\ImagineManager)`


---

<a name="getImagine" id="getImagine"></a>
### `getImagine()`

Get the imagine driver

#### Return
`(\Imagine\Image\ImagineInterface)`

