# Laravel Image
Laravel Image is an image manipulation package for Laravel 4 based on the [PHP Imagine library](https://github.com/avalanche123/Imagine). It is inspired by [Croppa](https://github.com/BKWLD/croppa) as it use specially formatted urls to do the manipulations. For example, if you have an image at this path:

    /uploads/photo.jpg

To create a 300x300 version of this image in black and white, you use the path:

    /uploads/photo-image(300x300-crop-grayscale).jpg

It supports basic image manipulations such as resize, crop, rotation and flip. It also supports effects such as negative, grayscale, gamma, colorize and blur. You can also define custom filters for greater flexibility.