import Image from '../Image';

test('generating url with size', () => {
    const image = new Image();
    const url = image.url('path/to/image.jpg', 300, 300);
    expect(url).toEqual('/path/to/image-image(300x300).jpg');
});

test('generating url with string filter', () => {
    const image = new Image();
    const url = image.url('path/to/image.jpg', 'small');
    expect(url).toEqual('/path/to/image-image(small).jpg');
});

test('generating url with array filter', () => {
    const image = new Image();
    const url = image.url('path/to/image.jpg', ['small', 'bw']);
    expect(url).toEqual('/path/to/image-image(small-bw).jpg');
});

test('generating url with filters object', () => {
    const image = new Image();
    const url = image.url('path/to/image.jpg', {
        small: true,
        rotate: 90,
    });
    expect(url).toEqual('/path/to/image-image(small-rotate(90)).jpg');
});

test('generating url with filters object and size', () => {
    const image = new Image();
    const url = image.url('path/to/image.jpg', 300, 300, {
        small: true,
        rotate: 90,
    });
    expect(url).toEqual('/path/to/image-image(300x300-small-rotate(90)).jpg');
});

test('generating url with config', () => {
    const image = new Image();
    const url = image.url('path/to/image.jpg', 300, 300, {
        small: true,
        rotate: 90,
        format: '{dirname}/{filters}/{basename}.{extension}',
        filters_format: '{filter}',
        filter_format: '{key}-{value}',
        filter_separator: '/',
    });
    expect(url).toEqual('/path/to/300x300/small/rotate-90/image.jpg');
});
