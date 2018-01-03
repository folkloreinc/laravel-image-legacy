/* globals LARAVEL_IMAGE_CONFIG */
import UrlGenerator from './UrlGenerator';

const urlGenerator = new UrlGenerator({
    ...(LARAVEL_IMAGE_CONFIG || null),
});

const image = {
    url: (...args) => urlGenerator.make(...args),
};

export {
    UrlGenerator,
};

export default image;
