ImageManipulator
=====================

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

### <a name="make" id="make"></a> `make($path, $options = array())`

Make an image and apply options

#### Arguments
- `$path` `(string)` The path of the image
- `$options` `(array)` The manipulations to apply on the image

#### Return
`(\Imagine\Image\ImageInterface)`

---

### <a name="open" id="open"></a> `open($path)`

Open an image from the source

#### Arguments
- `$path` `(string)` The path of the image

#### Return
`(\Imagine\Image\ImageInterface)`

---

### <a name="save" id="save"></a> `save($image, $path)`

Save an image to the source

#### Arguments
- `$image` `(\Imagine\Image\ImageInterface)` 
- `$path` 

#### Return
`(string)`

---

### <a name="format" id="format"></a> `format($path)`

Return an URL to process the image

#### Arguments
- `$path` `(string)` 

#### Return
`(array)`

---

### <a name="thumbnail" id="thumbnail"></a> `thumbnail($image, $width = null, $height = null, $crop = true)`

Create a thumbnail from an image

#### Arguments
- `$image` `(\Imagine\Image\ImageInterface|string)` An image instance or the path to an image
- `$width` `(integer)` 
- `$height` 
- `$crop` 

#### Return
`(\Imagine\Image\ImageInterface)`

---

### <a name="getSource" id="getSource"></a> `getSource()`

Get the image source

#### Return
`(\Folklore\Image\Contracts\Source)`

---

### <a name="setSource" id="setSource"></a> `setSource($source)`

Set the image source

#### Arguments
- `$source` `(\Folklore\Image\Contracts\Source)` The source of the factory

#### Return
`(\Folklore\Image\ImageManipulator)`

---

### <a name="getMemoryLimit" id="getMemoryLimit"></a> `getMemoryLimit()`

Get the memory limit

#### Return
`(string)`

---

### <a name="setMemoryLimit" id="setMemoryLimit"></a> `setMemoryLimit($limit)`

Set the memory limit

#### Arguments
- `$limit` `(string)` The memory limit

#### Return
`(\Folklore\Image\ImageManipulator)`

---

### <a name="getImagineManager" id="getImagineManager"></a> `getImagineManager()`

Get the imagine manager

#### Return
`(\Folklore\Image\ImagineManager)`

---

### <a name="getImagine" id="getImagine"></a> `getImagine()`

Get the imagine driver

#### Return
`(\Imagine\Image\ImagineInterface)`
