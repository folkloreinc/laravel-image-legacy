/* globals LARAVEL_IMAGE_CONFIG */
import Image from './Image';

var image = new Image(Object.assign({}, LARAVEL_IMAGE_CONFIG || null));

export { Image };

export default image;