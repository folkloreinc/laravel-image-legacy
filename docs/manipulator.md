Image Manipulator
================================================
The Image Manipulator is used to interact with image files on a specific source. When you call `image()->source('local')`, it returns an ImageManipulator.

You can access it with from the Image facade:
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

## <a name="setSource" id="setSource"></a>`setSource($source)`


---

## <a name="getSource" id="getSource"></a>`getSource()`
