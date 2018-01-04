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

### <a name="make" id="make"></a> `make($path, $options)`

Make an image and apply options

#### Arguments
- `(string)` `$path` 
- `(array)` `$options` 

#### Return
---

### <a name="open" id="open"></a> `open($path)`

Open an image from the source

#### Arguments
- `(string)` `$path` 

#### Return
---

### <a name="save" id="save"></a> `save($image, $path)`

Save an image to the source

#### Arguments
- `(\Imagine\Image\ImageInterface)` `$image` 
- `()` `$path` 

#### Return
---

### <a name="format" id="format"></a> `format($path)`

Return an URL to process the image

#### Arguments
- `(string)` `$path` 

#### Return
---

### <a name="thumbnail" id="thumbnail"></a> `thumbnail($image, $width, $height, $crop)`

Create a thumbnail from an image

#### Arguments
- `(\Imagine\Image\ImageInterface|string)` `$image` 
- `(integer)` `$width` 
- `()` `$height` 
- `()` `$crop` 

#### Return
---

### <a name="getSource" id="getSource"></a> `getSource()`

Get the image source

#### Arguments

#### Return
---

### <a name="setSource" id="setSource"></a> `setSource($source)`

Set the image source

#### Arguments
- `(\Folklore\Image\Contracts\Source)` `$source` 

#### Return
---

### <a name="getMemoryLimit" id="getMemoryLimit"></a> `getMemoryLimit()`

Get the memory limit

#### Arguments

#### Return
---

### <a name="setMemoryLimit" id="setMemoryLimit"></a> `setMemoryLimit($limit)`

Set the memory limit

#### Arguments
- `(string)` `$limit` 

#### Return
---

### <a name="getImagineManager" id="getImagineManager"></a> `getImagineManager()`

Get the imagine manager

#### Arguments

#### Return
---

### <a name="getImagine" id="getImagine"></a> `getImagine()`

Get the imagine driver

#### Arguments

#### Return
