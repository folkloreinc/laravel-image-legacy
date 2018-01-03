/* globals LARAVEL_IMAGE_CONFIG */
import Image from './Image';

const image = new Image({
    ...(LARAVEL_IMAGE_CONFIG || null),
});

export {
    Image,
};

export default image;
