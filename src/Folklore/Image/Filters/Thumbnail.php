<?php

namespace Folklore\Image\Filters;

use Folklore\Image\Contracts\FilterWithValue as FilterWithValueContract;
use Imagine\Image\ImageInterface;
use Imagine\Image\Box;
use Imagine\Image\Point;

class Thumbnail implements FilterWithValueContract
{
    public function apply(ImageInterface $image, $value = [])
    {
        if (is_array($value)) {
            $width = array_get($value, 'width', null);
            $height = array_get($value, 'height', null);
            $crop = array_get($value, 'crop', false);
        } else {
            $values = explode(',', $value);
            list($width, $height) = $values;
            $crop = isset($values[2]) ? $values[2]:true;
        }

        //Get new size
        $imageSize = $image->getSize();
        $newWidth = $width === null ? $imageSize->getWidth():$width;
        $newHeight = $height === null ? $imageSize->getHeight():$height;
        $size = new Box($newWidth, $newHeight);

        $ratios = array(
            $size->getWidth() / $imageSize->getWidth(),
            $size->getHeight() / $imageSize->getHeight()
        );

        $thumbnail = $image->copy();

        $thumbnail->usePalette($image->palette());
        $thumbnail->strip();

        if (!$crop) {
            $ratio = min($ratios);
        } else {
            $ratio = max($ratios);
        }

        if ($crop) {
            $imageSize = $thumbnail->getSize()->scale($ratio);
            $thumbnail->resize($imageSize);

            $x = max(0, round(($imageSize->getWidth() - $size->getWidth()) / 2));
            $y = max(0, round(($imageSize->getHeight() - $size->getHeight()) / 2));

            $cropPositions = $this->getCropPositions($crop);

            if ($cropPositions[0] === 'top') {
                $y = 0;
            } elseif ($cropPositions[0] === 'bottom') {
                $y = $imageSize->getHeight() - $size->getHeight();
            }

            if ($cropPositions[1] === 'left') {
                $x = 0;
            } elseif ($cropPositions[1] === 'right') {
                $x = $imageSize->getWidth() - $size->getWidth();
            }

            $point = new Point($x, $y);

            $thumbnail->crop($point, $size);
        } else {
            if (!$imageSize->contains($size)) {
                $imageSize = $imageSize->scale($ratio);
                $thumbnail->resize($imageSize);
            } else {
                $imageSize = $thumbnail->getSize()->scale($ratio);
                $thumbnail->resize($imageSize);
            }
        }

        return $thumbnail;
    }

    /**
     * Return crop positions from the crop parameter
     *
     * @return array
     */
    protected function getCropPositions($crop)
    {
        $crop = $crop === true ? 'center':$crop;

        $cropPositions = explode('_', $crop);
        if (sizeof($cropPositions) === 1) {
            if ($cropPositions[0] === 'top' || $cropPositions[0] === 'bottom' || $cropPositions[0] === 'center') {
                $cropPositions[] = 'center';
            } elseif ($cropPositions[0] === 'left' || $cropPositions[0] === 'right') {
                array_unshift($cropPositions, 'center');
            }
        }

        return $cropPositions;
    }
}
