/* globals LARAVEL_IMAGE_CONFIG */
import UrlGenerator from './UrlGenerator';

var urlGenerator = new UrlGenerator(Object.assign({}, LARAVEL_IMAGE_CONFIG || null));

var image = {
    url: function url() {
        return urlGenerator.make.apply(urlGenerator, arguments);
    }
};

export { UrlGenerator };

export default image;