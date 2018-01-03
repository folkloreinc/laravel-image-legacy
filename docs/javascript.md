Javascript Helper
================================================

We provide a javascript helper to generate images url in the frontend. The file is published in `public/vendor/folklore/image/image.js`. You can simply add the javascript tag in your layout:

```php
<script type="text/javascript" src="{{ asset('vendor/folklore/image/image.js') }}"></script>
```

The helper is now available as a `LaravelImage` global variable. You can use it like this:

```js
const url = LaravelImage.url('path/to/image', 300, 300, {
    rotate: 90,
});

// or

const url = LaravelImage.url('path/to/image', {
    width: 300,
    height: 300,
    rotate: 90,
});
```

## Npm package
If you prefer, you can use the npm package:

```shell
$ npm install laravel-image --save
```

```js
import LaravelImage from 'laravel-image';

const url = LaravelImage.url('path/to/image', 300, 300, {
    rotate: 90,
});

// or

import { UrlGenerator } from 'laravel-image';

const urlGenerator = new UrlGenerator({
    // custom pattern options
});
const url = urlGenerator.make('path/to/image', 300, 300, {
    rotate: 90,
});
```
