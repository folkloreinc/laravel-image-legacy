import UrlGenerator from '../UrlGenerator';

test('generating url with size', () => {
    const urlGenerator = new UrlGenerator();
    const url = urlGenerator.make('path/to/image.jpg', 300, 300);
    expect(url).toEqual('/path/to/image-filters(300x300).jpg');
});

test('generating url with string filter', () => {
    const urlGenerator = new UrlGenerator();
    const url = urlGenerator.make('path/to/image.jpg', 'small');
    expect(url).toEqual('/path/to/image-filters(small).jpg');
});

test('generating url with array filter', () => {
    const urlGenerator = new UrlGenerator();
    const url = urlGenerator.make('path/to/image.jpg', ['small', 'bw']);
    expect(url).toEqual('/path/to/image-filters(small-bw).jpg');
});

test('generating url with filters object', () => {
    const urlGenerator = new UrlGenerator();
    const url = urlGenerator.make('path/to/image.jpg', {
        small: true,
        rotate: 90,
    });
    expect(url).toEqual('/path/to/image-filters(small-rotate(90)).jpg');
});

test('generating url with filters object and size', () => {
    const urlGenerator = new UrlGenerator();
    const url = urlGenerator.make('path/to/image.jpg', 300, 300, {
        small: true,
        rotate: 90,
    });
    expect(url).toEqual('/path/to/image-filters(300x300-small-rotate(90)).jpg');
});

test('generating url with config', () => {
    const urlGenerator = new UrlGenerator();
    const url = urlGenerator.make('path/to/image.jpg', 300, 300, {
        small: true,
        rotate: 90,
        format: '{dirname}/{filters}/{basename}.{extension}',
        filters_format: '{filter}',
        filter_format: '{key}-{value}',
        filter_separator: '/',
    });
    expect(url).toEqual('/path/to/300x300/small/rotate-90/image.jpg');
});
